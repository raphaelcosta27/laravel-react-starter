<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class ProfileProtectionTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    public function test_non_administrator_edits_another_profile_and_its_permissions(): void
    {
        $other = Profile::factory()->create();
        $permission = $this->permission('gerar_relatorio');

        $this->actingAs($this->userWith('editar_perfil', 'gerar_relatorio'))
            ->patch("/admin/profiles/{$other->id}", ['name' => 'Novo', 'permissions' => [$permission->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame('Novo', $other->fresh()->name);
        $this->assertSame(1, $other->profilePermissions()->count());
    }

    public function test_non_administrator_cannot_change_the_master_profile(): void
    {
        $administrator = Profile::administrator();

        $this->actingAs($this->userWith('editar_perfil'))
            ->patch("/admin/profiles/{$administrator->id}", ['name' => 'Tomado'])
            ->assertForbidden();

        $this->assertNotSame('Tomado', $administrator->fresh()->name);
    }

    public function test_non_administrator_cannot_open_the_master_profile_form(): void
    {
        $administrator = Profile::administrator();

        $this->actingAs($this->userWith('editar_perfil'))
            ->get("/admin/profiles/{$administrator->id}/edit")
            ->assertForbidden();
    }

    public function test_non_administrator_cannot_change_their_own_profile(): void
    {
        $user = $this->userWith('editar_perfil');
        $permission = $this->permission('gerar_relatorio');

        $this->actingAs($user)
            ->patch("/admin/profiles/{$user->profile_id}", ['permissions' => [$permission->id]])
            ->assertForbidden();

        $this->assertFalse(User::find($user->id)->can('gerar_relatorio'));
    }

    public function test_administrator_can_rename_the_master_profile(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->patch("/admin/profiles/{$administrator->profile_id}", ['name' => 'Master'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Master', Profile::administrator()->name);
    }
}
