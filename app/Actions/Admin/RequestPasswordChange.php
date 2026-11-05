<?php

namespace App\Actions\Admin;

use App\Actions\IssueTemporaryPassword;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RequestPasswordChange
{
    public function __construct(private IssueTemporaryPassword $issueTemporaryPassword) {}

    /**
     * Trocar a senha já encerra as sessões, pelo middleware auth.session.
     * Devolve falso quando o envio falha.
     *
     * @throws ValidationException
     */
    public function handle(User $user): bool
    {
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'user' => __('This action is not available for inactive accounts.'),
            ]);
        }

        $user->requirePasswordChange();

        return $this->issueTemporaryPassword->handle($user);
    }
}
