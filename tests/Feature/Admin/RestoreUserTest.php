<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class RestoreUserTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    public function test_comes_back_with_status_and_links(): void
    {
        $profile = Profile::factory()->create();
        $user = User::factory()->withProfile($profile)->inactive()->create();
        UserPermission::create([
            'user_id' => $user->id,
            'permission_id' => $this->permission('gerar_relatorio')->id,
            'effect' => 'grant',
        ]);
        $user->delete();

        $this->actingAs($this->administrator())
            ->post("/admin/users/{$user->id}/restore")
            ->assertSessionHas('flash.type', 'success');

        $fresh = User::find($user->id);

        $this->assertFalse($fresh->is_active);
        $this->assertSame($profile->id, $fresh->profile_id);
        $this->assertTrue($fresh->can('gerar_relatorio'));
    }

    public function test_requires_an_active_profile_when_the_previous_one_is_deleted(): void
    {
        $profile = Profile::factory()->create();
        $user = User::factory()->withProfile($profile)->create();
        $user->delete();
        $profile->delete();

        $this->actingAs($this->administrator())
            ->post("/admin/users/{$user->id}/restore")
            ->assertSessionHasErrors('profile_id');

        $this->assertSoftDeleted($user);
    }

    public function test_chosen_active_profile_is_applied(): void
    {
        $previous = Profile::factory()->create();
        $user = User::factory()->withProfile($previous)->create();
        $user->delete();
        $previous->delete();
        $chosen = Profile::factory()->create();

        $this->actingAs($this->administrator())
            ->post("/admin/users/{$user->id}/restore", ['profile_id' => $chosen->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($chosen->id, User::find($user->id)->profile_id);
    }

    public function test_deleted_profile_cannot_be_chosen(): void
    {
        $previous = Profile::factory()->create();
        $user = User::factory()->withProfile($previous)->create();
        $user->delete();
        $previous->delete();
        $other = Profile::factory()->create();
        $other->delete();

        $this->actingAs($this->administrator())
            ->post("/admin/users/{$user->id}/restore", ['profile_id' => $other->id])
            ->assertSessionHasErrors('profile_id');
    }

    public function test_listing_informs_when_the_profile_is_deleted(): void
    {
        $profile = Profile::factory()->create();
        $user = User::factory()->withProfile($profile)->create();
        $user->delete();
        $profile->delete();

        $this->actingAs($this->administrator())
            ->get('/admin/users?tab=trashed')
            ->assertInertia(fn (Assert $page) => $page->where('rows.data.0.profile_deleted', true)->etc());
    }

    public function test_route_requires_the_permission(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();
        $user->delete();

        $this->actingAs($this->userWith())->post("/admin/users/{$user->id}/restore")->assertForbidden();
    }

    public function test_deleted_administrator_is_restored_only_by_an_administrator(): void
    {
        $this->administrator();
        $target = $this->administrator();
        $target->delete();

        $this->actingAs($this->userWith('restaurar_usuario'))
            ->post("/admin/users/{$target->id}/restore")
            ->assertForbidden();

        $this->assertSoftDeleted($target);

        $this->actingAs($this->administrator())
            ->post("/admin/users/{$target->id}/restore")
            ->assertSessionHasNoErrors();

        $this->assertNotSoftDeleted($target);
    }

    public function test_active_user_answers_not_found(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();

        $this->actingAs($this->administrator())
            ->post("/admin/users/{$user->id}/restore", ['profile_id' => Profile::administrator()->id])
            ->assertNotFound();

        $this->assertFalse($user->fresh()->isAdministrator());
    }

    public function test_non_administrator_cannot_give_the_master_profile_on_restoration(): void
    {
        $previous = Profile::factory()->create();
        $user = User::factory()->withProfile($previous)->create();
        $user->delete();
        $previous->delete();

        $this->actingAs($this->userWith('restaurar_usuario'))
            ->post("/admin/users/{$user->id}/restore", ['profile_id' => Profile::administrator()->id])
            ->assertForbidden();

        $this->assertSoftDeleted($user);
    }
}
