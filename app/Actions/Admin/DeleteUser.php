<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteUser
{
    public function __construct(private EnsureAdministratorRemains $ensureAdministratorRemains) {}

    /**
     * Ponto único de exclusão de contas, em qualquer tela.
     */
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->ensureAdministratorRemains->handle($user);

            $user->personalData?->delete();
            $user->delete();
        });
    }
}
