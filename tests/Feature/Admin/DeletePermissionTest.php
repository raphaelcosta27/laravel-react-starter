<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\ProfilePermission;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class DeletePermissionTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function destroy(Permission $permission): TestResponse
    {
        return $this->actingAs(User::factory()->administrator()->create())
            ->from('/admin/permissions')
            ->delete("/admin/permissions/{$permission->id}");
    }

    private function adjust(User $user, Permission $permission, string $effect): UserPermission
    {
        return UserPermission::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'effect' => $effect,
        ]);
    }

    public function test_unlinked_permission_is_deleted_and_its_record_preserved(): void
    {
        $permission = Permission::factory()->create();

        $this->destroy($permission)
            ->assertRedirect('/admin/permissions')
            ->assertSessionHas('flash.type', 'success');

        $this->assertSoftDeleted($permission);
    }

    public function test_system_permission_is_refused_with_the_reason(): void
    {
        $permission = Permission::factory()->create();
        $permission->is_system = true;
        $permission->save();

        $this->destroy($permission)->assertSessionHasErrors('permission');

        $this->assertNotSoftDeleted($permission);
    }

    public function test_permission_in_a_profile_with_users_is_refused(): void
    {
        $user = $this->userWith('gerar_relatorio');

        $this->destroy($this->permission('gerar_relatorio'))->assertSessionHasErrors('permission');

        $this->assertNotSoftDeleted($this->permission('gerar_relatorio'));
        $this->assertNotNull($user->fresh());
    }

    public function test_active_grant_is_refused(): void
    {
        $permission = Permission::factory()->create();
        $this->adjust(User::factory()->create(), $permission, 'grant');

        $this->destroy($permission)->assertSessionHasErrors('permission');
    }

    public function test_active_revocation_is_refused(): void
    {
        $permission = Permission::factory()->create();
        $this->adjust(User::factory()->create(), $permission, 'revoke');

        $this->destroy($permission)->assertSessionHasErrors('permission');
    }

    public function test_permission_in_a_profile_without_users_is_deleted(): void
    {
        $this->profileWith('gerar_relatorio');
        $permission = $this->permission('gerar_relatorio');

        $this->destroy($permission)->assertSessionHasNoErrors();

        $this->assertSoftDeleted($permission);
        $this->assertSame(1, ProfilePermission::where('permission_id', $permission->id)->count());
    }

    public function test_link_only_through_deleted_users_allows_the_deletion(): void
    {
        $user = $this->userWith('gerar_relatorio');
        $permission = $this->permission('gerar_relatorio');
        $this->adjust($user, $permission, 'grant');
        $user->delete();

        $this->destroy($permission)->assertSessionHasNoErrors();

        $this->assertSoftDeleted($permission);
    }

    public function test_deleted_adjustment_does_not_count_as_a_link(): void
    {
        $permission = Permission::factory()->create();
        $this->adjust(User::factory()->create(), $permission, 'grant')->delete();

        $this->destroy($permission)->assertSessionHasNoErrors();
    }

    public function test_deleted_profile_link_does_not_count(): void
    {
        $user = $this->userWith('gerar_relatorio');
        $permission = $this->permission('gerar_relatorio');
        ProfilePermission::where('profile_id', $user->profile_id)->first()->delete();

        $this->destroy($permission)->assertSessionHasNoErrors();
    }
}
