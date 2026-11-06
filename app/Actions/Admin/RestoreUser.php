<?php

namespace App\Actions\Admin;

use App\Actions\RestoreUserData;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RestoreUser
{
    public function __construct(private RestoreUserData $restoreUserData) {}

    /**
     * O usuário volta com a situação e os vínculos anteriores. Se o perfil dele
     * também foi excluído, é preciso escolher um perfil ativo.
     *
     * @throws ValidationException
     */
    public function handle(User $user, ?Profile $profile = null): void
    {
        if ($user->profile === null && $profile === null) {
            throw ValidationException::withMessages([
                'profile_id' => __('Choose an active profile to restore this user.'),
            ]);
        }

        DB::transaction(function () use ($user, $profile) {
            $user->restore();
            $this->restoreUserData->handle($user, 'user');

            if ($user->profile === null) {
                $user->forceFill(['profile_id' => $profile->id])->save();
                $user->unsetRelation('profile');
            }
        });
    }
}
