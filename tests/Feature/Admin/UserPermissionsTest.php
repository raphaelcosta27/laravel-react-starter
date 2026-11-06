<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function save(User $user, array $adjustments): TestResponse
    {
        return $this->actingAs(User::factory()->administrator()->create())
            ->put("/admin/users/{$user->id}/permissions", ['adjustments' => $adjustments]);
    }

    private function adjustment(string $code, string $effect): array
    {
        return ['permission_id' => $this->permission($code)->id, 'effect' => $effect];
    }

    public function test_screen_shows_the_state_and_origin_of_each_permission(): void
    {
        $user = $this->userWith('do_perfil', 'revogada');
        $this->permission('fora_do_perfil');
        $this->permission('concedida');

        UserPermission::create(['user_id' => $user->id, 'permission_id' => $this->permission('concedida')->id, 'effect' => 'grant']);
        UserPermission::create(['user_id' => $user->id, 'permission_id' => $this->permission('revogada')->id, 'effect' => 'revoke']);

        $this->actingAs(User::factory()->administrator()->create())
            ->get("/admin/users/{$user->id}/permissions")
            ->assertInertia(function (Assert $page) {
                $page->component('Admin/Users/Index')->where('form.canAdjust', true);

                $rows = collect($page->toArray()['props']['form']['modules'])->flatMap(fn (array $module) => $module['permissions'])->keyBy('code');

                $state = fn (string $code) => [$rows[$code]['assigned'], $rows[$code]['inProfile']];

                $this->assertSame([true, true], $state('do_perfil'));
                $this->assertSame([true, false], $state('concedida'));
                $this->assertSame([false, true], $state('revogada'));
                $this->assertSame([false, false], $state('fora_do_perfil'));
            });
    }

    public function test_grant_gives_access_without_changing_the_profile(): void
    {
        $user = $this->userWith();

        $this->save($user, [$this->adjustment('gerar_relatorio', 'grant')])
            ->assertSessionHas('flash.type', 'success');

        $this->assertTrue(User::find($user->id)->can('gerar_relatorio'));
        $this->assertSame(0, Profile::find($user->profile_id)->profilePermissions()->count());
    }

    public function test_revocation_does_not_affect_other_users_of_the_profile(): void
    {
        $user = $this->userWith('gerar_relatorio');
        $colleague = User::factory()->withProfile(Profile::find($user->profile_id))->create();

        $this->save($user, [$this->adjustment('gerar_relatorio', 'revoke')]);

        $this->assertFalse(User::find($user->id)->can('gerar_relatorio'));
        $this->assertTrue(User::find($colleague->id)->can('gerar_relatorio'));
    }

    public function test_removing_the_adjustment_returns_to_the_profile(): void
    {
        $user = $this->userWith('gerar_relatorio');

        $this->save($user, [$this->adjustment('gerar_relatorio', 'revoke')]);
        $this->save($user, []);

        $this->assertTrue(User::find($user->id)->can('gerar_relatorio'));
    }

    public function test_removing_fills_deleted_at_and_adjusting_again_restores_the_row(): void
    {
        $user = $this->userWith();

        $this->save($user, [$this->adjustment('gerar_relatorio', 'grant')]);
        $row = UserPermission::where('user_id', $user->id)->first();

        $this->save($user, []);
        $this->assertSoftDeleted($row);

        $this->save($user, [$this->adjustment('gerar_relatorio', 'revoke')]);

        $this->assertNotSoftDeleted($row);
        $this->assertSame('revoke', $row->fresh()->effect);
        $this->assertSame(1, UserPermission::withTrashed()->where('user_id', $user->id)->count());
    }

    public function test_adjustments_are_kept_when_the_profile_changes(): void
    {
        $user = $this->userWith();
        $this->save($user, [$this->adjustment('gerar_relatorio', 'grant')]);

        $this->actingAs(User::factory()->administrator()->create())
            ->patch("/admin/users/{$user->id}", ['profile_id' => Profile::factory()->create()->id])
            ->assertSessionHasNoErrors();

        $this->assertTrue(User::find($user->id)->can('gerar_relatorio'));
    }

    public function test_invalid_effect_and_deleted_permission_are_refused(): void
    {
        $user = $this->userWith();
        $deleted = $this->permission('antiga');
        $deleted->delete();

        $this->save($user, [$this->adjustment('gerar_relatorio', 'outro')])
            ->assertSessionHasErrors('adjustments.0.effect');

        $this->save($user, [['permission_id' => $deleted->id, 'effect' => 'grant']])
            ->assertSessionHasErrors('adjustments.0.permission_id');
    }
}
