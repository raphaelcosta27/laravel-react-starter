<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\ProfilePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class RestoreProfileAndPermissionTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    public function test_profile_comes_back_with_its_permissions(): void
    {
        $profile = $this->profileWith('gerar_relatorio');
        $profile->delete();

        $this->actingAs($this->administrator())
            ->post("/admin/profiles/{$profile->id}/restore")
            ->assertSessionHas('flash.type', 'success');

        $this->assertNotSoftDeleted($profile);
        $this->assertSame(1, ProfilePermission::where('profile_id', $profile->id)->count());
    }

    public function test_permission_comes_back_with_its_links(): void
    {
        $user = $this->userWith('gerar_relatorio');
        $permission = $this->permission('gerar_relatorio');
        $permission->delete();

        $this->assertFalse(User::find($user->id)->can('gerar_relatorio'));

        $this->actingAs($this->administrator())
            ->post("/admin/permissions/{$permission->id}/restore")
            ->assertSessionHas('flash.type', 'success');

        $this->assertTrue(User::find($user->id)->can('gerar_relatorio'));
    }

    public function test_profile_with_a_name_in_use_is_refused(): void
    {
        $profile = Profile::factory()->create(['name' => 'Financeiro']);
        $profile->delete();
        Profile::factory()->create(['name' => 'Financeiro']);

        $this->actingAs($this->administrator())
            ->post("/admin/profiles/{$profile->id}/restore")
            ->assertSessionHasErrors('profile');

        $this->assertSoftDeleted($profile);
    }

    public function test_permission_with_a_code_in_use_is_refused(): void
    {
        $permission = Permission::factory()->create(['code' => 'gerar_relatorio']);
        $permission->delete();
        Permission::factory()->create(['code' => 'gerar_relatorio']);

        $this->actingAs($this->administrator())
            ->post("/admin/permissions/{$permission->id}/restore")
            ->assertSessionHasErrors('permission');

        $this->assertSoftDeleted($permission);
    }

    public function test_listing_shows_the_deletion_date(): void
    {
        $this->freezeSecond();
        $permission = Permission::factory()->create();
        $permission->delete();

        $this->actingAs($this->administrator())
            ->get('/admin/permissions?tab=trashed')
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.data.0.deleted_at', now()->format('d/m/Y H:i'))
                ->etc());
    }

    public function test_routes_require_their_permissions(): void
    {
        $profile = Profile::factory()->create();
        $profile->delete();
        $permission = Permission::factory()->create();
        $permission->delete();
        $user = $this->userWith();

        $this->actingAs($user)->post("/admin/profiles/{$profile->id}/restore")->assertForbidden();
        $this->actingAs($user)->post("/admin/permissions/{$permission->id}/restore")->assertForbidden();
    }

    public function test_user_with_the_permission_is_released(): void
    {
        $profile = Profile::factory()->create();
        $profile->delete();

        $this->actingAs($this->userWith('restaurar_perfil'))
            ->post("/admin/profiles/{$profile->id}/restore")
            ->assertSessionHasNoErrors();

        $this->assertNotSoftDeleted($profile);
    }

    public function test_active_record_answers_not_found(): void
    {
        $profile = Profile::factory()->create();

        $this->actingAs($this->administrator())
            ->post("/admin/profiles/{$profile->id}/restore")
            ->assertNotFound();
    }
}
