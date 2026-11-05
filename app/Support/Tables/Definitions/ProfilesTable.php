<?php

namespace App\Support\Tables\Definitions;

use App\Models\Profile;
use App\Models\ProfilePermission;
use App\Models\User;
use App\Support\Tables\TableDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProfilesTable extends TableDefinition
{
    public function query(): Builder
    {
        return Profile::query()->withCount('users')->with('profilePermissions.permission');
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => __('Name'), 'sortable' => true, 'searchable' => true, 'filter' => 'text'],
            'description' => ['label' => __('Description'), 'searchable' => true, 'filter' => 'text'],
            'users_count' => [
                'label' => __('Users'),
                'sortable' => true,
                'filter' => 'number',
                'expression' => User::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('users.profile_id', 'profiles.id'),
            ],
            'deleted_at' => ['label' => __('Deleted at')],
        ];
    }

    public function defaultSort(): string
    {
        return 'name';
    }

    public function tabs(): array
    {
        return [
            'active' => ['label' => __('Actives'), 'apply' => fn (Builder $query) => $query],
            'trashed' => [
                'label' => __('Deleted'),
                'apply' => fn (Builder $query) => $query->onlyTrashed(),
                'visible' => $this->actor?->can('restaurar_perfil') ?? false,
            ],
        ];
    }

    public function summaries(): array
    {
        return ['users_count' => ['sum']];
    }

    /** @param Profile $row */
    public function transform(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'description' => $row->description,
            'users_count' => $row->users_count,
            'system_key' => $row->system_key,
            'deleted_at' => $row->deleted_at?->format('d/m/Y H:i'),
            'permissions' => $row->profilePermissions
                ->filter(fn (ProfilePermission $link) => $link->permission !== null)
                ->map(fn (ProfilePermission $link) => $link->permission->only('id', 'title', 'code'))
                ->values(),
        ];
    }

    /** @param Profile $row */
    public function rowAbilities(User $actor, Model $row): array
    {
        $available = ! $row->trashed();

        return [
            'update' => $available && $actor->can('update', $row),
            'delete' => $available && $row->system_key === null && $actor->can('delete', $row),
            'restore' => ! $available && $actor->can('restore', $row),
            'users' => $available && $actor->can('visualizar_perfil'),
        ];
    }
}
