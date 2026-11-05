<?php

namespace App\Actions\Admin;

use App\Actions\IssueTemporaryPassword;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReactivateUser
{
    public function __construct(private IssueTemporaryPassword $issueTemporaryPassword) {}

    /**
     * Novo cadastro de um e-mail excluído segue as regras de criação, e o acesso
     * só é possível pelo e-mail enviado. Ajustes individuais anteriores não voltam.
     */
    public function handle(User $user, string $name, Profile $profile): User
    {
        DB::transaction(function () use ($user, $name, $profile) {
            $user->restore();
            $user->permissionAdjustments->each->delete();

            $user->forceFill([
                'name' => $name,
                'profile_id' => $profile->id,
                'is_active' => true,
                'email_verified_at' => null,
                'must_change_password' => true,
            ])->save();
        });

        $user->unsetRelation('profile');
        $user->wasReactivated = true;
        $user->emailFailed = ! $this->issueTemporaryPassword->handle($user);

        return $user;
    }
}
