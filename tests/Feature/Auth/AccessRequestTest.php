<?php

namespace Tests\Feature\Auth;

use App\Actions\CreateUser;
use App\Models\User;
use App\Notifications\WelcomeUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccessRequestTest extends TestCase
{
    use RefreshDatabase;

    private function newUser(): User
    {
        Notification::fake();

        return app(CreateUser::class)->handle('Ana', 'ana@example.com', 'senha-temporaria');
    }

    public function test_screen_is_available_to_guests(): void
    {
        $this->get('/access/request')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/RequestAccess')->etc());
    }

    public function test_logged_user_cannot_open_the_screen(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/access/request')
            ->assertRedirect(route('dashboard'));
    }

    public function test_expired_password_is_replaced_and_sent(): void
    {
        $user = $this->newUser();

        $this->travel(61)->minutes();

        $this->post('/access/request', ['email' => $user->email])->assertSessionHas('status');

        $user->refresh();

        $this->assertFalse(Hash::check('senha-temporaria', $user->password));
        $this->assertTrue($user->temporary_password_expires_at->isFuture());
        $this->assertFalse($user->temporaryPasswordExpired());

        Notification::assertSentToTimes($user, WelcomeUser::class, 2);

        $this->post('/login', ['email' => $user->email, 'password' => 'senha-temporaria'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_new_password_gives_access(): void
    {
        $user = $this->newUser();

        $this->travel(61)->minutes();

        $this->post('/access/request', ['email' => $user->email]);

        $sent = Notification::sent($user, WelcomeUser::class)->last();

        $this->post('/login', ['email' => $user->email, 'password' => $sent->temporaryPassword]);

        $this->assertAuthenticated();
    }

    public function test_valid_password_is_not_replaced(): void
    {
        $user = $this->newUser();
        $status = $this->post('/access/request', ['email' => 'inexistente@example.com'])->getSession()->get('status');

        $this->post('/access/request', ['email' => $user->email])->assertSessionHas('status', $status);

        $this->assertTrue(Hash::check('senha-temporaria', $user->fresh()->password));

        Notification::assertSentToTimes($user, WelcomeUser::class, 1);
    }

    public function test_account_with_own_password_is_not_changed(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/access/request', ['email' => $user->email])->assertSessionHas('status');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        Notification::assertNothingSent();
    }

    public function test_unknown_email_sends_nothing(): void
    {
        Notification::fake();

        $this->post('/access/request', ['email' => 'inexistente@example.com'])->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_screen_shows_the_answer(): void
    {
        $this->from('/access/request')
            ->followingRedirects()
            ->post('/access/request', ['email' => 'inexistente@example.com'])
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/RequestAccess')
                ->where('status', __('If your temporary password has expired, a new one is on its way to your email.'))
                ->etc());
    }

    public function test_attempts_are_limited(): void
    {
        foreach (range(1, 6) as $attempt) {
            $this->post('/access/request', ['email' => 'ana@example.com'])->assertRedirect();
        }

        $this->post('/access/request', ['email' => 'ana@example.com'])->assertTooManyRequests();
    }
}
