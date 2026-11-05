<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DeletedUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_is_closed_on_the_next_request(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticated();

        $user->delete();
        $this->app['auth']->forgetGuards();

        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_deleted_user_cannot_log_in(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);

        $this->assertGuest();
    }

    public function test_forgot_password_answers_as_for_a_missing_account(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->delete();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasErrors(['email' => trans('passwords.user')]);

        $this->post('/forgot-password', ['email' => 'ninguem@example.com'])
            ->assertSessionHasErrors(['email' => trans('passwords.user')]);

        Notification::assertNothingSent();
    }

    public function test_access_request_answers_as_for_a_missing_account(): void
    {
        Notification::fake();
        $user = User::factory()->create(['temporary_password_expires_at' => now()->subMinute()]);
        $user->delete();

        $this->post('/access/request', ['email' => $user->email])->assertSessionHas('status');

        Notification::assertNothingSent();
    }
}
