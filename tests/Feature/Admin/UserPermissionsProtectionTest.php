<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class UserPermissionsProtectionTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function payload(): array
    {
        return ['adjustments' => [['permission_id' => $this->permission('gerar_relatorio')->id, 'effect' => 'grant']]];
    }

    public function test_user_with_the_permission_changes_another_common_user(): void
    {
        $target = $this->userWith();

        $this->actingAs($this->userWith('gerenciar_permissoes_usuario', 'gerar_relatorio'))
            ->put("/admin/users/{$target->id}/permissions", $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertTrue(User::find($target->id)->can('gerar_relatorio'));
    }

    public function test_non_administrator_cannot_change_an_administrator(): void
    {
        $target = User::factory()->administrator()->create();

        $this->actingAs($this->userWith('gerenciar_permissoes_usuario'))
            ->put("/admin/users/{$target->id}/permissions", $this->payload())
            ->assertForbidden();

        $this->assertSame(0, UserPermission::count());
    }

    public function test_non_administrator_cannot_change_themselves(): void
    {
        $user = $this->userWith('gerenciar_permissoes_usuario');

        $this->actingAs($user)
            ->put("/admin/users/{$user->id}/permissions", $this->payload())
            ->assertForbidden();

        $this->assertSame(0, UserPermission::count());
    }

    public function test_route_requires_the_permission(): void
    {
        $target = $this->userWith();
        $user = $this->userWith();

        $this->actingAs($user)->get("/admin/users/{$target->id}/permissions")->assertForbidden();
        $this->actingAs($user)->put("/admin/users/{$target->id}/permissions", $this->payload())->assertForbidden();
    }

    public function test_administrator_shows_everything_active_without_adjustments(): void
    {
        $target = User::factory()->administrator()->create();
        $this->permission('gerar_relatorio');

        $this->actingAs(User::factory()->administrator()->create())
            ->get("/admin/users/{$target->id}/permissions")
            ->assertInertia(fn (Assert $page) => $page
                ->where('form.canAdjust', false)
                ->etc());
    }

    public function test_adjustments_are_refused_for_administrators(): void
    {
        $target = User::factory()->administrator()->create();

        $this->actingAs(User::factory()->administrator()->create())
            ->put("/admin/users/{$target->id}/permissions", $this->payload())
            ->assertSessionHasErrors('user');

        $this->assertSame(0, UserPermission::count());
    }
}
