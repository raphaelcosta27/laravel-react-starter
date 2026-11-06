<?php

namespace App\Support\Dashboard\Blocks;

use App\Models\ChangeHistory;
use App\Models\User;
use App\Support\Dashboard\Block;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Só o que está em lang/pt_BR/history.php aparece: campos técnicos ficam de fora, e
 * os valores nem são lidos, para que nunca cheguem ao navegador.
 */
class MyActivity implements Block
{
    private const LIMIT = 5;

    /** Uma ação vira uma linha por campo: basta ler o fim do histórico da pessoa. */
    private const ROWS = 200;

    public function key(): string
    {
        return 'MyActivity';
    }

    public function title(): string
    {
        return __('Your recent activity');
    }

    public function permission(): ?string
    {
        return null;
    }

    public function available(): bool
    {
        return true;
    }

    public function data(User $user): array
    {
        $catalog = trans('history', [], 'pt_BR');

        $items = $this->known($user, $catalog)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::ROWS)
            ->get(['operation_id', 'operation', 'table_name', 'field', 'created_at'])
            ->groupBy('operation_id')
            ->take(self::LIMIT)
            ->map(fn (Collection $rows) => $this->item($rows, $catalog))
            ->values();

        return ['items' => $items->all()];
    }

    /**
     * @param  array<string, array<string, mixed>>  $catalog
     * @return Builder<ChangeHistory>
     */
    private function known(User $user, array $catalog): Builder
    {
        return ChangeHistory::query()
            ->where('user_id', $user->id)
            ->where(function (Builder $query) use ($catalog) {
                foreach ($catalog['fields'] as $table => $fields) {
                    $query->orWhere(fn (Builder $known) => $known
                        ->where('table_name', $table)
                        ->whereIn('field', array_keys($fields)));
                }
            });
    }

    /**
     * @param  Collection<int, ChangeHistory>  $rows
     * @param  array<string, array<string, mixed>>  $catalog
     * @return array{entity: string, action: string, fields: array<int, string>, created_at: string}
     */
    private function item(Collection $rows, array $catalog): array
    {
        $first = $rows->first();

        return [
            'entity' => $catalog['tables'][$first->table_name],
            'action' => $catalog['actions'][$first->operation],
            // Só a alteração diz o que mudou: ao criar, todos os campos seriam listados.
            'fields' => $first->operation === ChangeHistory::UPDATED
                ? $rows->map(fn (ChangeHistory $row) => $catalog['fields'][$row->table_name][$row->field])->values()->all()
                : [],
            'created_at' => $first->created_at->toIso8601String(),
        ];
    }
}
