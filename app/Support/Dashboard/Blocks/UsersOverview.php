<?php

namespace App\Support\Dashboard\Blocks;

use App\Models\User;
use App\Support\Dashboard\Block;

class UsersOverview implements Block
{
    public function key(): string
    {
        return 'UsersOverview';
    }

    public function title(): string
    {
        return __('Users');
    }

    public function permission(): ?string
    {
        return 'visualizar_usuario';
    }

    public function available(): bool
    {
        return true;
    }

    public function data(User $user): array
    {
        return ['metrics' => [
            $this->metric('active', __('Actives'), User::active()->count(), ['tab' => 'active']),
            $this->metric('inactive', __('Inactives'), User::inactive()->count(), ['tab' => 'inactive']),
            $this->metric(
                'confirmation_pending',
                __('Pending confirmation'),
                User::confirmationPending()->count(),
                ['tab' => 'all', 'filter' => ['confirmed' => 0]],
            ),
            $this->metric(
                'deleted',
                __('Deleted'),
                User::onlyTrashed()->count(),
                $user->can('restaurar_usuario') ? ['tab' => 'trashed'] : null,
            ),
        ]];
    }

    /**
     * @param  array<string, mixed>|null  $query
     * @return array{key: string, label: string, value: int, url: string|null}
     */
    private function metric(string $key, string $label, int $value, ?array $query): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'url' => $query === null ? null : route('admin.users.index', $query),
        ];
    }
}
