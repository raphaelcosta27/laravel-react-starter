<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LastAdministratorTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    public function test_last_active_administrator_cannot_be_inactivated(): void
    {
        $user = $this->administrator();

        $this->actingAs($user)
            ->patch("/admin/users/{$user->id}/status", ['is_active' => false])
            ->assertSessionHasErrors('user');

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_last_active_administrator_cannot_be_deleted(): void
    {
        $user = $this->administrator();

        $this->actingAs($user)->delete("/admin/users/{$user->id}")->assertSessionHasErrors('user');

        $this->assertNotSoftDeleted($user);
    }

    public function test_last_active_administrator_cannot_lose_the_profile(): void
    {
        $user = $this->administrator();

        $this->actingAs($user)
            ->patch("/admin/users/{$user->id}", ['profile_id' => Profile::default()->id])
            ->assertSessionHasErrors('user');

        $this->assertTrue($user->fresh()->isAdministrator());
    }

    public function test_last_active_administrator_cannot_delete_their_own_account(): void
    {
        $user = $this->administrator();

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrors('user');

        $this->assertNotSoftDeleted($user);
        $this->assertAuthenticated();
    }

    public function test_inactive_administrator_does_not_count(): void
    {
        $user = $this->administrator();
        User::factory()->administrator()->inactive()->create();

        $this->actingAs($user)->delete("/admin/users/{$user->id}")->assertSessionHasErrors('user');
    }

    public function test_deleted_administrator_does_not_count(): void
    {
        $user = $this->administrator();
        $this->administrator()->delete();

        $this->actingAs($user)->delete("/admin/users/{$user->id}")->assertSessionHasErrors('user');
    }

    public function test_allows_when_another_active_administrator_remains(): void
    {
        $user = $this->administrator();
        $other = $this->administrator();

        $this->actingAs($user)
            ->patch("/admin/users/{$other->id}/status", ['is_active' => false])
            ->assertSessionHasNoErrors();

        $this->assertFalse($other->fresh()->is_active);
    }

    public function test_allows_changing_the_profile_when_another_remains(): void
    {
        $user = $this->administrator();
        $other = $this->administrator();

        $this->actingAs($user)
            ->patch("/admin/users/{$other->id}", ['profile_id' => Profile::default()->id])
            ->assertSessionHasNoErrors();

        $this->assertFalse($other->fresh()->isAdministrator());
    }
}
