<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\ProfilePermission;
use App\Models\User;
use App\Models\UserPermission;
use Database\Seeders\AdminPanelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class PrivilegeEscalationTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private const CODE = 'gerar_relatorio';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();
    }

    private function adjustment(string $effect): array
    {
        return ['permission_id' => $this->permission(self::CODE)->id, 'effect' => $effect];
    }

    private function adjust(User $target, string $effect): UserPermission
    {
        return UserPermission::create(['user_id' => $target->id, ...$this->adjustment($effect)]);
    }

    private function assertHolds(bool $expected, User $user): void
    {
        $this->assertSame($expected, User::find($user->id)->can(self::CODE));
    }

    public function test_a_permission_the_actor_lacks_cannot_be_granted_to_a_user(): void
    {
        $target = $this->userWith();

        $this->actingAs($this->userWith('gerenciar_permissoes_usuario'))
            ->put("/admin/users/{$target->id}/permissions", ['adjustments' => [$this->adjustment('grant')]])
            ->assertForbidden();

        $this->assertSame(0, UserPermission::count());
    }

    public function test_a_permission_the_actor_has_can_be_granted_to_a_user(): void
    {
        $target = $this->userWith();

        $this->actingAs($this->userWith('gerenciar_permissoes_usuario', self::CODE))
            ->put("/admin/users/{$target->id}/permissions", ['adjustments' => [$this->adjustment('grant')]])
            ->assertSessionHasNoErrors();

        $this->assertHolds(true, $target);
    }

    public function test_a_repeated_permission_does_not_hide_a_grant(): void
    {
        $target = $this->userWith();

        $this->actingAs($this->userWith('gerenciar_permissoes_usuario'))
            ->put("/admin/users/{$target->id}/permissions", [
                'adjustments' => [$this->adjustment('revoke'), $this->adjustment('grant')],
            ])
            ->assertForbidden();

        $this->assertHolds(false, $target);
    }

    public function test_removing_a_revocation_counts_as_a_grant(): void
    {
        $target = $this->userWith(self::CODE);
        $this->adjust($target, 'revoke');

        $this->actingAs($this->userWith('gerenciar_permissoes_usuario'))
            ->put("/admin/users/{$target->id}/permissions", ['adjustments' => []])
            ->assertForbidden();

        $this->assertHolds(false, $target);
    }

    public function test_an_existing_grant_stays_when_the_actor_lacks_the_permission(): void
    {
        $target = $this->userWith();
        $this->adjust($target, 'grant');

        $this->actingAs($this->userWith('gerenciar_permissoes_usuario'))
            ->put("/admin/users/{$target->id}/permissions", ['adjustments' => [$this->adjustment('grant')]])
            ->assertSessionHasNoErrors();

        $this->assertHolds(true, $target);
    }

    public function test_revoking_is_not_granting(): void
    {
        $target = $this->userWith(self::CODE);

        $this->actingAs($this->userWith('gerenciar_permissoes_usuario'))
            ->put("/admin/users/{$target->id}/permissions", ['adjustments' => [$this->adjustment('revoke')]])
            ->assertSessionHasNoErrors();

        $this->assertHolds(false, $target);
    }

    public function test_a_new_profile_cannot_carry_a_permission_the_actor_lacks(): void
    {
        $this->actingAs($this->userWith('cadastrar_perfil'))
            ->post('/admin/profiles', ['name' => 'Novo', 'permissions' => [$this->permission(self::CODE)->id]])
            ->assertForbidden();

        $this->assertFalse(Profile::where('name', 'Novo')->exists());
    }

    public function test_a_new_profile_can_carry_a_permission_the_actor_has(): void
    {
        $this->actingAs($this->userWith('cadastrar_perfil', self::CODE))
            ->post('/admin/profiles', ['name' => 'Novo', 'permissions' => [$this->permission(self::CODE)->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Profile::firstWhere('name', 'Novo')->profilePermissions()->count());
    }

    public function test_a_permission_the_actor_lacks_cannot_be_added_to_a_profile(): void
    {
        $profile = $this->profileWith();

        $this->actingAs($this->userWith('editar_perfil'))
            ->patch("/admin/profiles/{$profile->id}", ['permissions' => [$this->permission(self::CODE)->id]])
            ->assertForbidden();

        $this->assertSame(0, $profile->profilePermissions()->count());
    }

    public function test_a_profile_keeps_a_permission_the_actor_lacks(): void
    {
        $profile = $this->profileWith(self::CODE);

        $this->actingAs($this->userWith('editar_perfil'))
            ->patch("/admin/profiles/{$profile->id}", ['name' => 'Outro', 'permissions' => [$this->permission(self::CODE)->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame('Outro', $profile->fresh()->name);
        $this->assertSame(1, $profile->profilePermissions()->count());
    }

    public function test_restoring_a_removed_link_counts_as_a_grant(): void
    {
        $profile = $this->profileWith(self::CODE);
        ProfilePermission::where('profile_id', $profile->id)->first()->delete();

        $this->actingAs($this->userWith('editar_perfil'))
            ->patch("/admin/profiles/{$profile->id}", ['permissions' => [$this->permission(self::CODE)->id]])
            ->assertForbidden();

        $this->assertSame(0, $profile->profilePermissions()->count());
    }

    public function test_a_user_cannot_be_created_in_a_profile_above_the_actor(): void
    {
        $this->actingAs($this->userWith('cadastrar_usuario'))
            ->post('/admin/users', ['name' => 'Ana', 'email' => 'ana@example.com', 'profile_id' => $this->profileWith(self::CODE)->id])
            ->assertForbidden();

        $this->assertFalse(User::where('email', 'ana@example.com')->exists());
    }

    public function test_a_user_can_be_created_in_a_profile_within_the_actor_reach(): void
    {
        $this->actingAs($this->userWith('cadastrar_usuario', self::CODE))
            ->post('/admin/users', ['name' => 'Ana', 'email' => 'ana@example.com', 'profile_id' => $this->profileWith(self::CODE)->id])
            ->assertSessionHasNoErrors();

        $this->assertTrue(User::where('email', 'ana@example.com')->exists());
    }

    public function test_a_user_cannot_be_moved_to_a_profile_above_the_actor(): void
    {
        $target = $this->userWith();
        $profile = $this->profileWith(self::CODE);

        $this->actingAs($this->userWith('editar_usuario'))
            ->patch("/admin/users/{$target->id}", ['profile_id' => $profile->id])
            ->assertForbidden();

        $this->assertNotSame($profile->id, $target->fresh()->profile_id);
    }

    public function test_a_user_can_move_to_a_profile_with_what_they_already_hold(): void
    {
        $target = $this->userWith(self::CODE);
        $profile = $this->profileWith(self::CODE);

        $this->actingAs($this->userWith('editar_usuario'))
            ->patch("/admin/users/{$target->id}", ['profile_id' => $profile->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($profile->id, $target->fresh()->profile_id);
    }

    public function test_a_restored_user_cannot_land_in_a_profile_above_the_actor(): void
    {
        $target = $this->userWith();
        $target->profile->delete();
        $target->delete();

        $this->actingAs($this->userWith('restaurar_usuario'))
            ->post("/admin/users/{$target->id}/restore", ['profile_id' => $this->profileWith(self::CODE)->id])
            ->assertForbidden();

        $this->assertTrue($target->fresh()->trashed());
    }

    public function test_an_administrator_without_links_grants_everything(): void
    {
        $administrator = User::factory()->administrator()->create();
        $target = $this->userWith();
        $profile = $this->profileWith(self::CODE);

        $this->actingAs($administrator)
            ->put("/admin/users/{$target->id}/permissions", ['adjustments' => [$this->adjustment('grant')]])
            ->assertSessionHasNoErrors();

        $this->actingAs($administrator)
            ->post('/admin/profiles', ['name' => 'Novo', 'permissions' => [$this->permission(self::CODE)->id]])
            ->assertSessionHasNoErrors();

        $this->actingAs($administrator)
            ->post('/admin/users', ['name' => 'Ana', 'email' => 'ana@example.com', 'profile_id' => $profile->id])
            ->assertSessionHasNoErrors();

        $this->assertHolds(true, $target);
        $this->assertSame(1, Profile::firstWhere('name', 'Novo')->profilePermissions()->count());
        $this->assertTrue(User::where('email', 'ana@example.com')->exists());
    }

    /** Conceder Visualizar ativa o que estava guardado, e isso exige possuir também. */
    public function test_granting_view_cannot_activate_a_dormant_permission_the_actor_lacks(): void
    {
        $this->seed(AdminPanelSeeder::class);

        $actor = $this->userWith('visualizar_usuario', 'visualizar_permissao', 'gerenciar_permissoes_usuario');
        $target = $this->userWith('editar_usuario');

        $this->actingAs($actor)
            ->put("/admin/users/{$target->id}/permissions", ['adjustments' => [
                ['permission_id' => $this->permission('visualizar_usuario')->id, 'effect' => 'grant'],
            ]])
            ->assertForbidden();
    }

    public function test_changing_the_profile_cannot_activate_a_dormant_grant_the_actor_lacks(): void
    {
        $this->seed(AdminPanelSeeder::class);

        $actor = $this->userWith('visualizar_usuario', 'editar_usuario');
        $target = $this->userWith();
        $reader = $this->profileWith('visualizar_perfil');

        UserPermission::create(['user_id' => $target->id, 'permission_id' => $this->permission('excluir_perfil')->id, 'effect' => 'grant']);

        $this->assertFalse($actor->can('changeProfile', [$target, $reader]));
    }

    public function test_a_profile_cannot_receive_view_that_activates_what_the_actor_lacks(): void
    {
        $this->seed(AdminPanelSeeder::class);

        $actor = $this->userWith('visualizar_perfil', 'editar_perfil', 'visualizar_usuario');
        $profile = $this->profileWith('editar_usuario');

        $this->actingAs($actor)
            ->patch("/admin/profiles/{$profile->id}", ['permissions' => [
                $this->permission('editar_usuario')->id,
                $this->permission('visualizar_usuario')->id,
            ]])
            ->assertForbidden();
    }

    public function test_the_user_screen_marks_what_the_actor_cannot_grant(): void
    {
        $this->permission(self::CODE);
        $target = $this->userWith();

        $this->actingAs($this->userWith('visualizar_usuario', 'gerenciar_permissoes_usuario'))
            ->get("/admin/users/{$target->id}/permissions")
            ->assertInertia(fn (Assert $page) => $page->where('form.modules', function ($modules) {
                $availability = collect($modules)->flatMap(fn ($module) => $module['permissions'])->pluck('available', 'code');

                return $availability[self::CODE] === false && $availability['gerenciar_permissoes_usuario'] === true;
            }));
    }

    public function test_the_profile_form_marks_what_the_actor_cannot_grant(): void
    {
        $this->permission(self::CODE);

        $this->actingAs($this->userWith('visualizar_perfil', 'cadastrar_perfil'))
            ->get('/admin/profiles/create')
            ->assertInertia(fn (Assert $page) => $page->where('form.modules', function ($modules) {
                $availability = collect($modules)->flatMap(fn ($module) => $module['permissions'])->pluck('available', 'code');

                return $availability[self::CODE] === false && $availability['cadastrar_perfil'] === true;
            }));
    }

    public function test_an_administrator_sees_every_permission_available(): void
    {
        $this->permission(self::CODE);

        $this->actingAs(User::factory()->administrator()->create())
            ->get('/admin/profiles/create')
            ->assertInertia(fn (Assert $page) => $page->where(
                'form.modules',
                fn ($modules) => collect($modules)->flatMap(fn ($module) => $module['permissions'])->every(fn ($permission) => $permission['available'] === true),
            ));
    }

    public function test_the_user_form_only_offers_profiles_within_reach(): void
    {
        $within = $this->profileWith('cadastrar_usuario');
        $above = $this->profileWith(self::CODE);

        $this->actingAs($this->userWith('visualizar_usuario', 'cadastrar_usuario'))
            ->get('/admin/users/create')
            ->assertInertia(fn (Assert $page) => $page->where('form.profileOptions', function ($options) use ($within, $above) {
                $values = collect($options)->pluck('value');

                return $values->contains((string) $within->id) && ! $values->contains((string) $above->id);
            }));
    }

    public function test_the_edit_form_keeps_the_current_profile(): void
    {
        $target = $this->userWith(self::CODE);
        $above = $this->profileWith('excluir_usuario');

        $this->actingAs($this->userWith('visualizar_usuario', 'editar_usuario'))
            ->get("/admin/users/{$target->id}/edit")
            ->assertInertia(fn (Assert $page) => $page->where('form.profileOptions', function ($options) use ($target, $above) {
                $values = collect($options)->pluck('value');

                return $values->contains((string) $target->profile_id) && ! $values->contains((string) $above->id);
            }));
    }
}
