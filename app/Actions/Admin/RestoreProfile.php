<?php

namespace App\Actions\Admin;

use App\Models\Profile;
use Illuminate\Validation\ValidationException;

class RestoreProfile
{
    /**
     * Os vínculos ficam intactos na exclusão, então voltam junto com o perfil.
     *
     * @throws ValidationException
     */
    public function handle(Profile $profile): void
    {
        if (Profile::where('name', $profile->name)->exists()) {
            throw ValidationException::withMessages([
                'profile' => __('Another profile already uses this name.'),
            ]);
        }

        $profile->restore();
    }
}
