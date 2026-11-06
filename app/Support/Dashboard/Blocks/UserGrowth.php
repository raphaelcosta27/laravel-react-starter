<?php

namespace App\Support\Dashboard\Blocks;

use App\Models\User;
use App\Support\Dashboard\Block;
use App\Support\Dashboard\DailySeries;

class UserGrowth implements Block
{
    public function key(): string
    {
        return 'UserGrowth';
    }

    public function title(): string
    {
        return __('New users');
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
        $days = (int) config('template.dashboard.growth_days');

        return DailySeries::fill($days, User::query()
            ->where('created_at', '>=', DailySeries::start($days))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day'));
    }
}
