<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\WelcomeUser;
use App\Support\Auth\PasswordCriteria;
use Throwable;

class IssueTemporaryPassword
{
    /**
     * Define uma senha temporária com validade e a envia ao usuário.
     * Devolve falso quando o envio falha.
     */
    public function handle(User $user, ?string $password = null): bool
    {
        $password ??= PasswordCriteria::generate();

        $user->forceFill([
            'password' => $password,
            'temporary_password_expires_at' => now()->addMinutes(config('template.temporary_password_expire')),
        ])->save();

        return $this->send($user, $password);
    }

    /**
     * Uma falha no envio não pode desfazer a conta: o usuário solicita uma nova
     * autenticação depois que a senha expirar, e quem chamou decide como avisar.
     */
    public function send(User $user, ?string $password = null): bool
    {
        try {
            $user->notify(new WelcomeUser($password));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
