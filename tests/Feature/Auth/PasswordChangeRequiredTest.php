<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PasswordChangeRequiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_routes_redirect_to_password_change(): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('password.change'));
        $this->actingAs($user)->get('/profile')->assertRedirect(route('password.change'));
    }

    public function test_password_change_screen_is_available_while_required(): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $this->actingAs($user)
            ->get('/password/change')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/ChangePassword'));
    }

    public function test_user_can_log_out_while_required(): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_screen_redirects_to_dashboard_when_not_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/password/change')->assertRedirect(route('dashboard'));
    }

    public function test_reactivation_applies_to_the_open_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();

        User::find($user->id)->requirePasswordChange();
        $user->refresh();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('password.change'));
    }

    public function test_unconfirmed_account_goes_to_confirmation_first(): void
    {
        $user = User::factory()->unverified()->mustChangePassword()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get('/password/change')->assertRedirect(route('verification.notice'));
    }

    public function test_unconfirmed_account_cannot_change_password(): void
    {
        $user = User::factory()->unverified()->mustChangePassword()->create();

        $this->actingAs($user)
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'Nova-senha-1',
                'password_confirmation' => 'Nova-senha-1',
            ])
            ->assertRedirect(route('verification.notice'));

        $this->assertTrue($user->fresh()->must_change_password);
    }
}
