<?php

namespace App\Actions;

use App\Models\User;
use App\Models\UserContact;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class SaveContact
{
    /**
     * O que não vale para o contato é limpo aqui, inclusive numa edição. Dois
     * envios iguais ao mesmo tempo passam pela validação e param no índice: a
     * mensagem do banco traz o valor, então ela não pode seguir até o log.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(User $user, array $data, ?UserContact $contact = null): void
    {
        $emergency = (bool) ($data['is_emergency'] ?? false);

        $attributes = [
            'type' => $data['type'],
            'value' => $data['value'],
            'is_whatsapp' => $data['type'] === UserContact::TYPE_PHONE && (bool) ($data['is_whatsapp'] ?? false),
            'is_emergency' => $emergency,
            'relationship' => $emergency ? $data['relationship'] : null,
            'contact_name' => $emergency ? $data['contact_name'] : null,
        ];

        try {
            $contact === null ? $user->contacts()->create($attributes) : $contact->update($attributes);
        } catch (UniqueConstraintViolationException $exception) {
            if (! str_contains($exception->getMessage(), 'active_value')) {
                throw $exception;
            }

            throw ValidationException::withMessages(['value' => __('You already have this contact.')]);
        }
    }
}
