<?php

namespace Tests\Feature\Auth;

use App\Actions\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class TemporaryPasswordExpiryTest extends TestCase
{
    use RefreshDatabase;

    private function newUser(): User
    {
        Notification::fake();

        return app(CreateUser::class)->handle('Ana', 'ana@example.com', 'senha-temporaria');
    }

    private function login(string $password = 'senha-temporaria')
    {
        return $this->post('/login', ['email' => 'ana@example.com', 'password' => $password]);
    }

    public function test_still_works_before_the_deadline(): void
    {
        $this->newUser();

        $this->travel(59)->minutes();

        $this->login();

        $this->assertAuthenticated();
    }

    public function test_expired_password_is_refused_with_its_own_message(): void
    {
        $this->newUser();

        $this->travel(61)->minutes();

        $this->login()->assertSessionHasErrors(['email' => trans('auth.temporary_password_expired')]);

        $this->assertGuest();
    }

    public function test_wrong_password_gets_the_generic_message(): void
    {
        $this->newUser();

        $this->travel(61)->minutes();

        $this->login('senha-errada')->assertSessionHasErrors(['email' => trans('auth.failed')]);
    }

    public function test_deadline_follows_the_configuration(): void
    {
        config(['template.temporary_password_expire' => 10]);

        $this->newUser();

        $this->travel(11)->minutes();

        $this->login()->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_open_session_ends_when_the_password_expires(): void
    {
        $user = $this->newUser();
        $user->markEmailAsVerified();

        $this->actingAs($user)->get('/password/change')->assertOk();

        $this->travel(61)->minutes();

        $this->get('/password/change')->assertRedirect(route('access.request'));

        $this->assertGuest();
    }

    public function test_password_change_clears_the_validity(): void
    {
        $user = $this->newUser();
        $user->markEmailAsVerified();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'senha-temporaria',
            'password' => 'nova-senha-segura',
            'password_confirmation' => 'nova-senha-segura',
        ])->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->temporary_password_expires_at);
    }

    public function test_password_reset_clears_the_validity(): void
    {
        $user = $this->newUser();

        $this->post('/reset-password', [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => 'nova-senha-segura',
            'password_confirmation' => 'nova-senha-segura',
        ])->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->temporary_password_expires_at);
    }

    public function test_password_chosen_by_the_user_never_expires(): void
    {
        $user = User::factory()->create();

        $this->travel(1)->years();

        $this->assertFalse($user->temporaryPasswordExpired());
    }
}
