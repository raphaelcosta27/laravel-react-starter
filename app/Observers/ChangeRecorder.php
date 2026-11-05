<?php

namespace App\Observers;

use App\Models\ChangeHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Grava um item de histórico por campo alterado. Os valores são os crus do
 * banco, sem casts, e passam por format() antes de serem gravados.
 */
class ChangeRecorder
{
    private const MASK = '********';

    private const BINARY = '[binary]';

    private const MAX_LENGTH = 10000;

    public function created(Model $model): void
    {
        $values = array_diff_key($model->getAttributes(), [$model->getKeyName() => true]);

        $this->record($model, ChangeHistory::CREATED, array_map(fn ($value) => [null, $value], $values));
    }

    public function updated(Model $model): void
    {
        $changes = [];

        foreach ($model->getChanges() as $field => $value) {
            $changes[$field] = [$model->getRawOriginal($field), $value];
        }

        $this->record($model, $this->updateOperation($model, $changes), $changes);
    }

    public function deleted(Model $model): void
    {
        if ($this->softDeletes($model) && ! $model->isForceDeleting()) {
            $column = $model->getDeletedAtColumn();

            $this->record($model, ChangeHistory::DELETED, [$column => [null, $model->getAttributes()[$column]]]);

            return;
        }

        $this->record($model, ChangeHistory::DELETED, array_map(fn ($value) => [$value, null], $model->getRawOriginal()));
    }

    /**
     * A restauração e a exclusão em massa chegam como atualização de "deleted_at".
     *
     * @param  array<string, array{mixed, mixed}>  $changes
     */
    private function updateOperation(Model $model, array $changes): string
    {
        $column = $this->softDeletes($model) ? $model->getDeletedAtColumn() : null;

        if ($column === null || ! array_key_exists($column, $changes)) {
            return ChangeHistory::UPDATED;
        }

        return $changes[$column][1] === null ? ChangeHistory::RESTORED : ChangeHistory::DELETED;
    }

    private function softDeletes(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }

    /** @param array<string, array{mixed, mixed}> $changes */
    private function record(Model $model, string $operation, array $changes): void
    {
        $ignored = [...config('template.history.ignored_fields'), $model->getCreatedAtColumn(), $model->getUpdatedAtColumn()];
        $masked = [...config('template.history.masked_fields'), ...$model->getHidden()];
        $changes = array_diff_key($changes, array_flip(array_filter($ignored)));

        if ($changes === []) {
            return;
        }

        $operationId = (string) Str::uuid();
        $userId = auth()->id();
        $now = now();
        $items = [];

        foreach ($changes as $field => [$old, $new]) {
            $sensitive = in_array($field, $masked, true);

            $items[] = [
                'operation_id' => $operationId,
                'operation' => $operation,
                'table_name' => $model->getTable(),
                'record_id' => (string) $model->getKey(),
                'field' => $field,
                'old_value' => $this->format($old, $sensitive),
                'new_value' => $this->format($new, $sensitive),
                'user_id' => $userId,
                'source' => $userId === null ? ChangeHistory::SOURCE_SYSTEM : ChangeHistory::SOURCE_USER,
                'created_at' => $now,
            ];
        }

        ChangeHistory::on($model->getConnectionName())->insert($items);
    }

    private function format(mixed $value, bool $sensitive): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($sensitive) {
            return self::MASK;
        }

        $text = match (true) {
            is_bool($value) => $value ? '1' : '0',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value),
        };

        return mb_check_encoding($text, 'UTF-8') ? mb_substr($text, 0, self::MAX_LENGTH) : self::BINARY;
    }
}
