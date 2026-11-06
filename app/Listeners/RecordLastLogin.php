<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * O evento só ocorre depois de todas as etapas de verificação. A gravação vai direto
 * à tabela: um acesso não é alteração de dados e não entra no histórico.
 */
class RecordLastLogin
{
    public function handle(Login $event): void
    {
        User::query()->toBase()->where('id', $event->user->getAuthIdentifier())->update(['last_login_at' => now()]);
    }
}
