<?php

namespace App\Support\Tables\Definitions;

use App\Models\Permission;
use App\Models\User;
use App\Support\Tables\TableDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PermissionsTable extends TableDefinition
{
    public function query(): Builder
    {
        return Permission::query();
    }

    public function columns(): array
    {
        return [
            'title' => ['label' => __('Title'), 'sortable' => true, 'searchable' => true, 'filter' => 'text'],
            'code' => ['label' => __('Code'), 'sortable' => true, 'searchable' => true, 'filter' => 'text'],
            'description' => ['label' => __('Description'), 'searchable' => true, 'filter' => 'text'],
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
