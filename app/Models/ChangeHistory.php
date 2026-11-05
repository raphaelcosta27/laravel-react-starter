<?php

namespace App\Models;

use App\Support\History\ImmutableBuilder;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Item de histórico: a mudança de um campo em um registro. Os itens são gravados
 * pelo ChangeRecorder e nunca mudam, por isso não há exclusão de nenhum tipo.
 */
#[Table('change_history')]
class ChangeHistory extends Model
{
    public const CREATED = 'created';

    public const UPDATED = 'updated';

    public const DELETED = 'deleted';

    public const RESTORED = 'restored';

    public const SOURCE_USER = 'user';

    public const SOURCE_SYSTEM = 'system';

    public $timestamps = false;

    protected static function booted(): void
    {
        $refuse = fn () => throw new LogicException('O histórico de alterações não pode ser alterado.');

        static::saving($refuse);
        static::deleting($refuse);
    }

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function newEloquentBuilder($query): ImmutableBuilder
    {
        return new ImmutableBuilder($query);
    }

    /** @param Builder<self> $query */
    public function scopeOf(Builder $query, Model $model): void
    {
        $query->where('table_name', $model->getTable())->where('record_id', (string) $model->getKey());
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
