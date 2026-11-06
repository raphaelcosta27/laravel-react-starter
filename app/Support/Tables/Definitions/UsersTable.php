<?php

namespace App\Support\Tables\Definitions;

use App\Models\Profile;
use App\Models\User;
use App\Support\Tables\TableDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UsersTable extends TableDefinition
{
    public function query(): Builder
    {
        return User::query()->with('profile');
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => __('Name'), 'sortable' => true, 'searchable' => true, 'filter' => 'text'],
            'email' => ['label' => __('Email'), 'sortable' => true, 'searchable' => true, 'filter' => 'text'],
            'profile_id' => [
                'label' => __('Profile'),
                'sortable' => true,
                'filter' => 'multiselect',
                'sort' => Profile::query()->select('name')->whereColumn('profiles.id', 'users.profile_id'),
            ],
            'is_active' => ['label' => __('Status'), 'filter' => 'boolean'],
            'confirmed' => [
                'label' => __('Account confirmation'),
                'filter' => 'boolean',
                'apply' => fn (Builder $query, mixed $value) => $value
                    ? $query->whereNotNull('email_verified_at')
                    : $query->confirmationPending(),
            ],
            'password_change_pending' => [
                'filter' => 'boolean',
                'apply' => fn (Builder $query, mixed $value) => $value
                    ? $query->passwordChangePending()
                    : $query->where('must_change_password', false),
            ],
            'temporary_password_expired' => [
                'filter' => 'boolean',
                'apply' => fn (Builder $query, mixed $value) => $value
                    ? $query->withExpiredTemporaryPassword()
                    : $query->where(fn (Builder $valid) => $valid
                        ->whereNull('temporary_password_expires_at')
                        ->orWhere('temporary_password_expires_at', '>=', now())),
            ],
            'created_at' => ['sortable' => true, 'filter' => 'date'],
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
            'all' => ['label' => __('All'), 'apply' => fn (Builder $query) => $query],
            'active' => ['label' => __('Actives'), 'apply' => fn (Builder $query) => $query->active()],
            'inactive' => ['label' => __('Inactives'), 'apply' => fn (Builder $query) => $query->inactive()],
            'trashed' => [
                'label' => __('Deleted'),
                'apply' => fn (Builder $query) => $query->onlyTrashed(),
                'visible' => $this->actor?->can('restaurar_usuario') ?? false,
            ],
        ];
    }

    /** @param User $row */
    public function transform(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'email' => $row->email,
            'profile_id' => $row->profile_id,
            'profile_name' => $row->profile?->name,
            'is_active' => $row->is_active,
            'confirmed' => $row->email_verified_at !== null,
            'password_change_pending' => $row->must_change_password,
            'temporary_password_expired' => $row->temporaryPasswordExpired(),
            'created_at' => $row->created_at,
            'deleted_at' => $row->deleted_at?->format('d/m/Y H:i'),
            'profile_deleted' => $row->profile === null,
        ];
    }

    /** @param User $row */
    public function exportValue(Model $row, string $column): string
    {
        return match ($column) {
            'profile_id' => (string) $row->profile?->name,
            'is_active' => $row->is_active ? __('Active') : __('Inactive'),
            'confirmed' => $row->email_verified_at !== null ? __('Confirmed') : __('Pending'),
            default => parent::exportValue($row, $column),
        };
    }

    /** @param User $row */
    public function rowAbilities(User $actor, Model $row): array
    {
        $available = ! $row->trashed();
        $confirmed = $row->email_verified_at !== null;

        return [
            'update' => $available && $actor->can('update', $row),
            'status' => $available && $actor->can('changeStatus', $row),
            'delete' => $available && $actor->can('delete', $row),
            'permissions' => $available && $actor->can('managePermissions', $row),
            'passwordChange' => $available && $row->is_active && $actor->can('requestPasswordChange', $row),
            'authenticationEmail' => $available && $row->is_active && ! $confirmed && $actor->can('sendAuthenticationEmail', $row),
            'restore' => ! $available && $actor->can('restore', $row),
        ];
    }
}
