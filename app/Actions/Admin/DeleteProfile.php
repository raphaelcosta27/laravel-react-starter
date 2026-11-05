<?php

namespace App\Actions\Admin;

use App\Models\Profile;
use Illuminate\Validation\ValidationException;

class DeleteProfile
{
    /**
     * Vale também para administradores: protege a integridade do sistema.
     *
     * @throws ValidationException
     */
    public function handle(Profile $profile): void
    {
        if ($profile->system_key !== null) {
            throw ValidationException::withMessages([
                'profile' => __('System profiles cannot be deleted.'),
            ]);
        }

        if ($profile->users()->exists()) {
            throw ValidationException::withMessages([
                'profile' => __('This profile has users and cannot be deleted.'),
            ]);
        }

        $profile->delete();
    }
}
