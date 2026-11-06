<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    public function test_creates_a_profile_with_permissions(): void
    {
        $permission = $this->permission('gerar_relatorio');

        $this->actingAs($this->administrator())
            ->post('/admin/profiles', [
                'name' => 'Financeiro',
                'description' => 'Equipe',
                'permissions' => [$permission->id],
            ])
            ->assertRedirect('/admin/profiles')
            ->assertSessionHas('flash.type', 'success');

        $profile = Profile::firstWhere('name', 'Financeiro');

        $this->assertSame('Equipe', $profile->description);
        $this->assertSame(1, $profile->profilePermissions()->count());
    }

    public function test_name_is_required_and_limited(): void
    {
        $actor = $this->administrator();

        $this->actingAs($actor)->post('/admin/profiles', ['name' => ''])->assertSessionHasErrors('name');
        $this->actingAs($actor)->post('/admin/profiles', ['name' => Str::random(101)])->assertSessionHasErrors('name');
    }

    public function test_description_is_optional_and_limited(): void
    {
        $actor = $this->administrator();

        $this->actingAs($actor)->post('/admin/profiles', ['name' => 'A'])->assertSessionHasNoErrors();
        $this->actingAs($actor)
            ->post('/admin/profiles', ['name' => 'B', 'description' => Str::random(256)])
            ->assertSessionHasErrors('description');
    }

    public function test_used_name_is_refused(): void
    {
        Profile::factory()->create(['name' => 'Financeiro']);

        $this->actingAs($this->administrator())
            ->post('/admin/profiles', ['name' => 'Financeiro'])
            ->assertSessionHasErrors('name');
    }

    public function test_name_of_a_deleted_profile_is_accepted(): void
    {
        Profile::factory()->create(['name' => 'Financeiro'])->delete();

        $this->actingAs($this->administrator())
            ->post('/admin/profiles', ['name' => 'Financeiro'])
            ->assertSessionHasNoErrors();
    }

    public function test_deleted_permission_cannot_be_chosen(): void
    {
        $permission = $this->permission('gerar_relatorio');
        $permission->delete();

        $this->actingAs($this->administrator())
            ->post('/admin/profiles', ['name' => 'A', 'permissions' => [$permission->id]])
            ->assertSessionHasErrors('permissions.0');
    }

    public function test_edit_keeps_its_own_name_and_accepts_partial_data(): void
    {
        $profile = Profile::factory()->create(['name' => 'Financeiro', 'description' => 'Antes']);

        $this->actingAs($this->administrator())
            ->patch("/admin/profiles/{$profile->id}", ['name' => 'Financeiro', 'description' => 'Depois'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->administrator())
            ->patch("/admin/profiles/{$profile->id}", ['name' => 'Contas'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Contas', $profile->fresh()->name);
        $this->assertSame('Depois', $profile->fresh()->description);
    }

    public function test_partial_edit_keeps_the_permissions(): void
    {
        $profile = $this->profileWith('gerar_relatorio');

        $this->actingAs($this->administrator())
            ->patch("/admin/profiles/{$profile->id}", ['name' => 'Novo nome'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $profile->profilePermissions()->count());
    }

    public function test_listing_counts_only_users_not_deleted(): void
    {
        $profile = Profile::factory()->create(['name' => 'Financeiro']);
        User::factory()->withProfile($profile)->create();
        User::factory()->withProfile($profile)->create()->delete();

        $this->actingAs($this->administrator())
            ->get('/admin/profiles?search=Financeiro')
            ->assertInertia(fn (Assert $page) => $page->where('rows.data.0.users_count', 1)->etc());
    }

    public function test_edit_screen_receives_the_selected_permissions(): void
    {
        $profile = $this->profileWith('gerar_relatorio');
        $permission = $this->permission('gerar_relatorio');

        $this->actingAs($this->administrator())
            ->get("/admin/profiles/{$profile->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Profiles/Index')
                ->where('form.mode', 'edit')
                ->where('form.record.id', $profile->id)
                ->where('form.selected', [$permission->id])
                ->has('form.modules.0.permissions', 1)
                ->etc());
    }

    public function test_create_screen_opens_over_the_listing(): void
    {
        $this->actingAs($this->administrator())
            ->get('/admin/profiles/create')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Profiles/Index')
                ->where('form.mode', 'create')
                ->where('form.record', null)
                ->where('form.selected', [])
                ->etc());
    }

    public function test_forms_require_the_listing_permission(): void
    {
        $profile = Profile::factory()->create();
        $user = $this->userWith('cadastrar_perfil', 'editar_perfil');

        $this->actingAs($user)->get('/admin/profiles/create')->assertForbidden();
        $this->actingAs($user)->get("/admin/profiles/{$profile->id}/edit")->assertForbidden();
    }

    public function test_each_route_requires_its_permission(): void
    {
        $profile = Profile::factory()->create();
        $user = $this->userWith();

        $this->actingAs($user)->get('/admin/profiles/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/profiles', ['name' => 'A'])->assertForbidden();
        $this->actingAs($user)->get("/admin/profiles/{$profile->id}/edit")->assertForbidden();
        $this->actingAs($user)->patch("/admin/profiles/{$profile->id}", ['name' => 'A'])->assertForbidden();
        $this->actingAs($user)->delete("/admin/profiles/{$profile->id}")->assertForbidden();
        $this->actingAs($user)->get("/admin/profiles/{$profile->id}/users")->assertForbidden();
    }
}
