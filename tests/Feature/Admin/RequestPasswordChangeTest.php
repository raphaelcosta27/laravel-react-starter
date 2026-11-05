<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use App\Notifications\WelcomeUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RequestPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private function target(array $attributes = []): User
    {
        return User::factory()->withProfile(Profile::factory()->create())->create($attributes);
    }

    private function request(User $target)
    {
        return $this->actingAs(User::factory()->administrator()->create())
            ->from('/admin/users')
            ->post("/admin/users/{$target->id}/password-change");
    }

    public function test_issues_a_temporary_password_and_invalidates_the_previous_one(): void
    {
        Notification::fake();
        $target = $this->target();

        $this->request($target)->assertSessionHas('flash.type', 'success');

        $fresh = $target->fresh();

        $this->assertFalse(Hash::check('password', $fresh->password));
        $this->assertTrue($fresh->must_change_password);
        $this->assertNotNull($fresh->temporary_password_expires_at);

        Notification::assertSentTo($target, WelcomeUser::class, fn (WelcomeUser $notification) => Hash::check($notification->temporaryPassword, $fresh->password));
    }

    public function test_open_sessions_are_closed(): void
    {
        Notification::fake();
        $target = $this->target();

        $this->post('/login', ['email' => $target->email, 'password' => 'password']);
        $this->get('/dashboard')->assertOk();
        $session = session()->all();

        $this->request($target);

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->withSession($session)->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_link_leads_to_the_password_change_on_a_confirmed_account(): void
    {
        Notification::fake();
        $target = $this->target();

        $this->request($target);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $target->id,
            'hash' => sha1($target->email),
        ]);

        $this->actingAs($target->fresh())->followingRedirects()->get($url)->assertOk();

        $this->actingAs($target->fresh())->get('/dashboard')->assertRedirect(route('password.change'));
    }

    public function test_inactive_account_is_refused(): void
    {
        Notification::fake();
        $target = $this->target(['is_active' => false]);

        $this->request($target)->assertSessionHasErrors('user');

        $this->assertTrue(Hash::check('password', $target->fresh()->password));

        Notification::assertNothingSent();
    }

    public function test_password_is_never_shown_in_the_response(): void
    {
        Notification::fake();
        $target = $this->target();
        $password = null;

        $response = $this->request($target);

        Notification::assertSentTo($target, WelcomeUser::class, function (WelcomeUser $notification) use (&$password) {
            $password = $notification->temporaryPassword;

            return true;
        });

        $this->assertStringNotContainsString($password, json_encode(session()->all()));
        $this->assertStringNotContainsString($password, $response->getContent());
    }

    public function test_route_requires_the_permission(): void
    {
        $this->actingAs(User::factory()->withProfile(Profile::factory()->create())->create())
            ->post('/admin/users/'.$this->target()->id.'/password-change')
            ->assertForbidden();
    }
}
