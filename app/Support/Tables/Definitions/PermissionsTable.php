<?php

namespace App\Support\Tables\Definitions;

use App\Models\Permission;
use App\Models\User;
use App\Support\Permissions\Modules;
use App\Support\Tables\TableDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PermissionsTable extends TableDefinition
{
    /**
     * A tela mostra um título por módulo, então o módulo vem antes de qualquer
     * ordem escolhida.
     */
    public function query(): Builder
    {
        $keys = Modules::keys();
        $cases = implode(' ', array_fill(0, count($keys), 'WHEN ? THEN ?'));
        $bindings = collect($keys)->flatMap(fn (string $key, int $position) => [$key, $position])->all();

        return Permission::query()->orderByRaw("CASE module {$cases} ELSE ? END", [...$bindings, count($keys)]);
    }

    public function columns(): array
    {
        return [
            'title' => ['label' => __('Title'), 'sortable' => true, 'searchable' => true, 'filter' => 'text'],
            'code' => ['label' => __('Code'), 'sortable' => true, 'searchable' => true, 'filter' => 'text'],
            'description' => ['label' => __('Description'), 'searchable' => true, 'filter' => 'text'],
            'module' => ['label' => __('Module'), 'filter' => 'multiselect'],
            'deleted_at' => ['label' => __('Deleted at')],
        ];
    }

    public function defaultSort(): string
    {
        return 'title';
    }

    public function tabs(): array
    {
        return [
            'active' => ['label' => __('Actives'), 'apply' => fn (Builder $query) => $query],
            'trashed' => [
                'label' => __('Deleted'),
                'apply' => fn (Builder $query) => $query->onlyTrashed(),
                'visible' => $this->actor?->can('restaurar_permissao') ?? false,
            ],
        ];
    }

    /** @param Permission $row */
    public function transform(Model $row): array
    {
        return [
            'id' => $row->id,
            'title' => $row->title,
            'code' => $row->code,
            'description' => $row->description,
            'module' => $row->module,
            'module_label' => Modules::label($row->module),
            'is_system' => $row->is_system,
            'deleted_at' => $row->deleted_at?->format('d/m/Y H:i'),
        ];
    }

    /** @param Permission $row */
    public function rowAbilities(User $actor, Model $row): array
    {
        $available = ! $row->trashed();

        return [
            'update' => $available && $actor->can('editar_permissao'),
            'delete' => $available && ! $row->is_system && $actor->can('excluir_permissao'),
            'restore' => ! $available && $actor->can('restaurar_permissao'),
        ];
    }
}
