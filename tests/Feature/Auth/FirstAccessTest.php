<?php

namespace Tests\Feature\Auth;

use App\Actions\CreateUser;
use App\Models\User;
use App\Notifications\WelcomeUser;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FirstAccessTest extends TestCase
{
    use RefreshDatabase;

    private function newUser(): User
    {
        Notification::fake();

        return app(CreateUser::class)->handle('Ana', 'ana@example.com', 'senha-temporaria');
    }

    private function link(User $user, int $minutes = 60): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes($minutes), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);
    }

    public function test_link_without_session_goes_through_login_and_reaches_password_change(): void
    {
        $user = $this->newUser();
        $link = $this->link($user);

        $this->get($link)->assertRedirect(route('login'));

        $this->post('/login', ['email' => $user->email, 'password' => 'senha-temporaria'])
            ->assertRedirect($link);

        $response = $this->get($link);

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->get($response->headers->get('Location'))->assertRedirect(route('password.change'));
    }

    public function test_link_with_session_confirms_without_new_login(): void
    {
        $user = $this->newUser();

        $response = $this->actingAs($user)->get($this->link($user));

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->get($response->headers->get('Location'))->assertRedirect(route('password.change'));
    }

    public function test_repeated_link_moves_to_the_pending_step(): void
    {
        $user = $this->newUser();
        $link = $this->link($user);

        $this->actingAs($user)->get($link);
        $response = $this->actingAs($user->fresh())->get($link);

        $this->get($response->headers->get('Location'))->assertRedirect(route('password.change'));
    }

    public function test_login_without_link_asks_for_confirmation(): void
    {
        $user = $this->newUser();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_expired_link_shows_notice(): void
    {
        $user = $this->newUser();

        $this->actingAs($user)
            ->get($this->link($user, -1))
            ->assertRedirect(route('verification.notice'));

        $this->get(route('verification.notice'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/VerifyEmail')
                ->where('status', 'verification-link-expired'));

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_link_validity_follows_the_configuration(): void
    {
        $user = $this->newUser();

        $this->assertSame(60, config('auth.verification.expire'));

        Notification::assertSentTo($user, WelcomeUser::class, function (WelcomeUser $notification) use ($user) {
            parse_str(parse_url($notification->toMail($user)->actionUrl, PHP_URL_QUERY), $query);

            return abs((int) $query['expires'] - now()->addMinutes(60)->timestamp) <= 2;
        });
    }

    public function test_link_of_another_account_shows_guidance_even_in_debug(): void
    {
        config(['app.debug' => true]);

        $owner = $this->newUser();
        $other = User::factory()->create();

        $this->actingAs($other)
            ->get($this->link($owner))
            ->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Error')
                ->where('status', 403)
                ->where('canLogout', true)
                ->etc());

        $this->assertNull($owner->fresh()->email_verified_at);
    }

    public function test_resend_uses_plain_link_without_password(): void
    {
        $user = $this->newUser();

        $this->actingAs($user)->post('/email/verification-notification');

        Notification::assertSentTo($user, VerifyEmail::class, fn ($notification) => ! $notification instanceof WelcomeUser);
    }
}
