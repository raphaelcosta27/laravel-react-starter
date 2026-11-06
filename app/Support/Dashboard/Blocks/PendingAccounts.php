<?php

namespace App\Support\Dashboard\Blocks;

use App\Models\User;
use App\Support\Dashboard\Block;

class PendingAccounts implements Block
{
    public function key(): string
    {
        return 'PendingAccounts';
    }

    public function title(): string
    {
        return __('Pending accounts');
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
            [
                'key' => 'password_change_pending',
                'label' => __('Password change pending'),
                'value' => User::passwordChangePending()->count(),
                'url' => route('admin.users.index', ['tab' => 'all', 'filter' => ['password_change_pending' => 1]]),
            ],
            [
                'key' => 'temporary_password_expired',
                'label' => __('Temporary password expired'),
                'value' => User::withExpiredTemporaryPassword()->count(),
                'url' => route('admin.users.index', ['tab' => 'all', 'filter' => ['temporary_password_expired' => 1]]),
            ],
        ]];
    }
}
