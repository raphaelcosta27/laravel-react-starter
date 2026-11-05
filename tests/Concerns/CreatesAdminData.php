<?php

namespace Tests\Concerns;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\ProfilePermission;
use App\Models\User;

trait CreatesAdminData
{
    protected function permission(string $code): Permission
    {
        return Permission::firstWhere('code', $code)
            ?? Permission::factory()->create(['code' => $code]);
    }

    protected function profileWith(string ...$codes): Profile
    {
        $profile = Profile::factory()->create();

        foreach ($codes as $code) {
            ProfilePermission::create([
                'profile_id' => $profile->id,
                'permission_id' => $this->permission($code)->id,
            ]);
        }

        return $profile;
    }

    protected function userWith(string ...$codes): User
    {
        return User::factory()->withProfile($this->profileWith(...$codes))->create();
    }

    /**
     * Devolve uma instância nova, como a que cada requisição carrega do banco.
     */
    protected function nextRequestAs(User $user): static
    {
        return $this->actingAs(User::find($user->id));
    }
}
