<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\WelcomeUser;
use Illuminate\Support\Str;
use Throwable;

class IssueTemporaryPassword
{
    /**
     * Define uma senha temporária com validade e a envia ao usuário.
     */
    public function handle(User $user, ?string $password = null): void
    {
        $password ??= Str::password(12, symbols: false);

        $user->forceFill([
            'password' => $password,
            'temporary_password_expires_at' => now()->addMinutes(config('template.temporary_password_expire')),
        ])->save();

        $this->send($user, $password);
    }

    /**
     * Uma falha no envio não pode desfazer a conta: o usuário solicita uma nova
     * autenticação depois que a senha expirar.
     */
    public function send(User $user, ?string $password = null): void
    {
        try {
            $user->notify(new WelcomeUser($password));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
