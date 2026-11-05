<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatusTest extends TestCase
{
    use RefreshDatabase;

    private function target(array $attributes = []): User
    {
        return User::factory()->withProfile(Profile::factory()->create())->create($attributes);
    }

    public function test_inactivating_closes_the_session_on_the_next_request(): void
    {
        $target = $this->target();
        $actor = User::factory()->administrator()->create();

        $this->actingAs($target)->get('/dashboard')->assertOk();

        $this->actingAs($actor)
            ->patch("/admin/users/{$target->id}/status", ['is_active' => false])
            ->assertSessionHas('flash.type', 'success');

        $this->actingAs(User::find($target->id))->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_activating_gives_back_the_access_with_the_same_data(): void
    {
        $target = $this->target(['is_active' => false]);

        $this->actingAs(User::factory()->administrator()->create())
            ->patch("/admin/users/{$target->id}/status", ['is_active' => true])
            ->assertSessionHasNoErrors();

        $this->post('/logout');

        $this->post('/login', ['email' => $target->email, 'password' => 'password'])
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($target->fresh());
    }

    public function test_status_is_required(): void
    {
        $target = $this->target();

        $this->actingAs(User::factory()->administrator()->create())
            ->patch("/admin/users/{$target->id}/status", [])
            ->assertSessionHasErrors('is_active');
    }
}
