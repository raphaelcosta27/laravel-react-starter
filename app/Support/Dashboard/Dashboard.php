<?php

namespace App\Support\Dashboard;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Monta a tela inicial: o que a pessoa não pode ver nem chega a ser calculado, e a
 * falha de um bloco fica nele.
 */
class Dashboard
{
    /**
     * @return array<int, array{key: string, title: string, failed: bool, data: array<string, mixed>|null}>
     */
    public function for(User $user): array
    {
        return collect(config('template.dashboard.blocks'))
            ->map(fn (string $block): Block => app($block))
            ->filter(fn (Block $block) => $this->visible($block, $user))
            ->map(fn (Block $block) => $this->render($block, $user))
            ->values()
            ->all();
    }

    private function visible(Block $block, User $user): bool
    {
        if (in_array($block->key(), config('template.dashboard.disabled'), true) || ! $block->available()) {
            return false;
        }

        return $block->permission() === null || $user->can($block->permission());
    }

    /**
     * @return array{key: string, title: string, failed: bool, data: array<string, mixed>|null}
     */
    private function render(Block $block, User $user): array
    {
        $result = ['key' => $block->key(), 'title' => $block->title()];

        try {
            return [...$result, 'failed' => false, 'data' => $block->data($user)];
        } catch (Throwable $exception) {
            Log::error("Falha no bloco {$block->key()} do painel.", ['exception' => $exception]);

            return [...$result, 'failed' => true, 'data' => null];
        }
    }
}
