<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ActiveUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_account_gets_the_notice_only_with_correct_credentials(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => trans('auth.inactive')]);

        $this->assertGuest();
    }

    public function test_inactive_account_with_wrong_password_gets_the_generic_answer(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'errada'])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);
    }

    public function test_open_session_is_closed_on_the_next_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_confirmation_link_is_refused(): void
    {
        $user = User::factory()->unverified()->inactive()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('login'));

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_password_change_screen_is_refused(): void
    {
        $user = User::factory()->inactive()->mustChangePassword()->create();

        $this->actingAs($user)->get('/password/change')->assertRedirect(route('login'));
    }

    public function test_forgot_password_answers_as_accepted_and_sends_nothing(): void
    {
        Notification::fake();
        $user = User::factory()->inactive()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertNothingSent();
    }

    public function test_access_request_keeps_the_answer_and_sends_nothing(): void
    {
        Notification::fake();
        $user = User::factory()->inactive()->create([
            'temporary_password_expires_at' => now()->subMinute(),
        ]);

        $this->post('/access/request', ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }
}
