<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class EnsureAdministratorRemains
{
    /**
     * Deve rodar dentro de uma transação. Bloqueia todas as contas ativas de
     * administrador, inclusive a do alvo: assim, dois administradores não
     * conseguem excluir um ao outro ao mesmo tempo.
     *
     * @throws ValidationException
     */
    public function handle(User $target): void
    {
        if (! $target->isAdministrator()) {
            return;
        }

        $remaining = User::where('profile_id', $target->profile_id)
            ->where('is_active', true)
            ->lockForUpdate()
            ->pluck('id')
            ->reject(fn (int $id) => $id === $target->id);

        if ($remaining->isEmpty()) {
            throw ValidationException::withMessages([
                'user' => __('The system must keep at least one active administrator.'),
            ]);
        }
    }
}
