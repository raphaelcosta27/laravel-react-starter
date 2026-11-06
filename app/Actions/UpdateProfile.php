<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateProfile
{
    private const PERSONAL_FIELDS = ['preferred_name', 'birth_date', 'gender', 'cpf'];

    /**
     * Campos ausentes ficam como estão. Duas contas com o mesmo CPF ao mesmo
     * tempo passam pela validação e param no índice: a mensagem do banco traz o
     * CPF, então ela não pode seguir até o log.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(User $user, array $data): void
    {
        try {
            DB::transaction(function () use ($user, $data) {
                $user->fill(Arr::only($data, ['name', 'email']));

                if ($user->isDirty('email')) {
                    $user->email_verified_at = null;
                }

                $user->save();

                $personal = Arr::only($data, self::PERSONAL_FIELDS);

                if ($personal !== []) {
                    $user->personalData()->updateOrCreate([], $personal);
                }
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (! str_contains($exception->getMessage(), 'active_cpf')) {
                throw $exception;
            }

            throw ValidationException::withMessages(['cpf' => __('This CPF is already in use.')]);
        }

        $user->unsetRelation('personalData');
    }
}
