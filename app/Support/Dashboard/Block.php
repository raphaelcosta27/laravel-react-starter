<?php

namespace App\Support\Dashboard;

use App\Models\User;

/**
 * Bloco da tela inicial. A chave é também o nome do componente em
 * resources/js/Components/Dashboard/Blocks.
 */
interface Block
{
    public function key(): string;

    public function title(): string;

    /** Código exigido, ou null para todas as pessoas. */
    public function permission(): ?string;

    /** Falso quando o recurso do bloco está desligado. */
    public function available(): bool;

    /** @return array<string, mixed> */
    public function data(User $user): array;
}
