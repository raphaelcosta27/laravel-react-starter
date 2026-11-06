<?php

namespace App\Support\Dashboard\Blocks;

use App\Models\User;
use App\Support\Dashboard\Block;

class Accesses implements Block
{
    public function key(): string
    {
        return 'Accesses';
    }

    public function title(): string
    {
        return __('Accesses');
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
        $days = (int) config('template.dashboard.access_days');

        return [
            'days' => $days,
            'active' => User::accessedSince(now()->subDays($days))->count(),
            'without_record' => User::withoutRecordedAccess()->count(),
        ];
    }
}
