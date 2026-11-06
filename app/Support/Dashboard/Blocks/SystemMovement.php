<?php

namespace App\Support\Dashboard\Blocks;

use App\Models\ChangeHistory;
use App\Models\User;
use App\Support\Dashboard\Block;
use App\Support\Dashboard\DailySeries;

/**
 * Conta ações, não campos: salvar um formulário com três mudanças é uma alteração.
 */
class SystemMovement implements Block
{
    public function key(): string
    {
        return 'SystemMovement';
    }

    public function title(): string
    {
        return __('System changes');
    }

    public function permission(): ?string
    {
        return 'visualizar_historico';
    }

    public function available(): bool
    {
        return true;
    }

    public function data(User $user): array
    {
        $days = (int) config('template.dashboard.movement_days');

        return DailySeries::fill($days, ChangeHistory::query()
            ->where('created_at', '>=', DailySeries::start($days))
            ->selectRaw('DATE(created_at) as day, COUNT(DISTINCT operation_id) as total')
            ->groupBy('day')
            ->pluck('total', 'day'));
    }
}
