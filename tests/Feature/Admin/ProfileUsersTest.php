<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class ProfileUsersTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    public function test_lists_only_the_users_of_the_profile_without_deleted_ones(): void
    {
        $profile = Profile::factory()->create(['name' => 'Financeiro']);
        User::factory()->withProfile($profile)->create(['name' => 'Ana']);
        User::factory()->withProfile($profile)->create(['name' => 'Excluída'])->delete();
        User::factory()->withProfile(Profile::factory()->create())->create(['name' => 'Outra']);

        $this->actingAs($this->userWith('visualizar_perfil'))
            ->get("/admin/profiles/{$profile->id}/users")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Profiles/Users')
                ->where('profile.name', 'Financeiro')
                ->has('rows.data', 1)
                ->where('rows.data.0.name', 'Ana')
                ->etc());
    }
}
