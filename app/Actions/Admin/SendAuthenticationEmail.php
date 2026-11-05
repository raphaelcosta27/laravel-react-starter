<?php

namespace App\Actions\Admin;

use App\Actions\IssueTemporaryPassword;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SendAuthenticationEmail
{
    public function __construct(private IssueTemporaryPassword $issueTemporaryPassword) {}

    /**
     * Sempre ativa a troca obrigatória: sem ela, uma conta que já havia trocado
     * a senha ficaria presa quando a nova senha temporária expirasse.
     * Devolve falso quando o envio falha.
     *
     * @throws ValidationException
     */
    public function handle(User $user): bool
    {
        if (! $user->is_active || $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'user' => __('This action is available only for active and unconfirmed accounts.'),
            ]);
        }

        $user->requirePasswordChange();

        return $this->issueTemporaryPassword->handle($user);
    }
}
