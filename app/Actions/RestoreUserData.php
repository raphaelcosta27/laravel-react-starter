<?php

namespace App\Actions;

use App\Models\User;
use App\Models\UserData;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class RestoreUserData
{
    /**
     * Quem chama está em uma transação: a recusa desfaz a volta da conta. O
     * índice decide o conflito, e a mensagem do banco, que traz o CPF, para aqui.
     *
     * @throws ValidationException
     */
    public function handle(User $user, string $errorKey): void
    {
        try {
            UserData::onlyTrashed()->firstWhere('user_id', $user->id)?->restore();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                $errorKey => __('This account cannot be restored because its CPF is in use by another account.'),
            ]);
        }

        $user->unsetRelation('personalData');
    }
}
