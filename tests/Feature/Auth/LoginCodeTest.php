<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\LoginCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class LoginCodeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['template.login.two_factor_enabled' => true]);

        Notification::fake();

        $this->user = User::factory()->create();
    }

    private function logIn(array $extra = []): TestResponse
    {
        return $this->post('/login', ['email' => $this->user->email, 'password' => 'password', ...$extra]);
    }

    private function sentCode(): string
    {
        $code = null;

        Notification::assertSentTo($this->user, LoginCode::class, function (LoginCode $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        return $code;
    }

    private function wrong(string $code): string
    {
        return $code === '000000' ? '000001' : '000000';
    }

    public function test_correct_credentials_lead_to_the_code_screen_without_access(): void
    {
        $this->logIn()->assertRedirect('/login/code');

        $this->assertGuest();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->sentCode());
    }

    public function test_the_code_screen_shows_the_validity_and_never_the_code(): void
    {
        $this->logIn();

        $response = $this->get('/login/code')
            ->assertInertia(fn (Assert $page) => $page->component('Auth/LoginCode')->where('minutes', 15));

        $this->assertStringNotContainsString($this->sentCode(), $response->getContent());
    }

    public function test_the_correct_code_completes_the_login(): void
    {
        $this->logIn();

        $this->post('/login/code', ['code' => $this->sentCode()])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_an_expired_code_is_refused_and_another_can_be_requested(): void
    {
        $this->logIn();
        $code = $this->sentCode();

        $this->travel(16)->minutes();

        $this->post('/login/code', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/login/code/resend')->assertSessionHasNoErrors();
        Notification::assertSentToTimes($this->user, LoginCode::class, 2);
    }

    public function test_the_validity_follows_the_configuration(): void
    {
        config(['template.login.code_expire' => 1]);

        $this->logIn();
        $code = $this->sentCode();

        $this->travel(2)->minutes();

        $this->post('/login/code', ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_a_wrong_code_keeps_the_validity_of_the_right_one(): void
    {
        $this->logIn();
        $code = $this->sentCode();

        $this->travel(10)->minutes();
        $this->post('/login/code', ['code' => $this->wrong($code)]);

        $this->travel(6)->minutes();
        $this->post('/login/code', ['code' => $code])->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_a_used_code_is_refused(): void
    {
        $this->logIn();
        $code = $this->sentCode();

        $this->post('/login/code', ['code' => $code]);
        $this->post('/logout');

        $this->logIn();
        $this->post('/login/code', ['code' => $code])->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_resending_invalidates_the_previous_code(): void
    {
        $this->logIn();
        $first = $this->sentCode();

        $this->post('/login/code/resend')->assertSessionHas('status');

        $this->post('/login/code', ['code' => $first === $this->sentCode() ? $this->wrong($first) : $first])
            ->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/login/code', ['code' => $this->sentCode()]);
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_codes_are_limited_counting_login_and_resend(): void
    {
        config(['template.login.code_send_limit' => 2]);

        $this->logIn();
        $this->post('/login/code/resend')->assertSessionHasNoErrors();
        $this->post('/login/code/resend')->assertSessionHasErrors('code');
        $this->logIn()->assertSessionHasErrors('email');

        Notification::assertSentToTimes($this->user, LoginCode::class, 2);
    }

    public function test_the_limit_ends_with_the_period(): void
    {
        config(['template.login.code_send_limit' => 1]);

        $this->logIn();
        $this->post('/login/code/resend')->assertSessionHasErrors('code');

        $this->travel(16)->minutes();

        $this->post('/login/code/resend')->assertSessionHasNoErrors();
    }

    public function test_the_last_wrong_attempt_invalidates_the_code_and_restarts_the_login(): void
    {
        $this->logIn();
        $code = $this->sentCode();

        foreach (range(1, 4) as $attempt) {
            $this->post('/login/code', ['code' => $this->wrong($code)])->assertSessionHasErrors('code');
        }

        $this->post('/login/code', ['code' => $this->wrong($code)])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->get('/login/code')->assertRedirect('/login');
        $this->post('/login/code', ['code' => $code])->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_the_attempts_follow_the_configuration(): void
    {
        config(['template.login.code_attempts' => 1]);

        $this->logIn();

        $this->post('/login/code', ['code' => $this->wrong($this->sentCode())])->assertRedirect('/login');
    }

    public function test_malformed_codes_are_refused(): void
    {
        $this->logIn();

        $this->post('/login/code', ['code' => 'abc'])->assertSessionHasErrors('code');
        $this->post('/login/code', [])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_a_restricted_page_leads_back_to_the_code_screen_and_is_the_destination(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');

        $this->logIn();

        $this->get('/admin/users')->assertRedirect('/login/code');

        $this->post('/login/code', ['code' => $this->sentCode()])->assertRedirect('/admin/users');
    }

    public function test_without_a_pending_login_the_code_routes_lead_to_the_login(): void
    {
        $this->get('/login/code')->assertRedirect('/login');
        $this->post('/login/code', ['code' => '123456'])->assertRedirect('/login');
        $this->post('/login/code/resend')->assertRedirect('/login');

        Notification::assertNothingSent();
    }

    public function test_wrong_password_and_unknown_account_get_the_same_answer_and_no_code(): void
    {
        $answer = ['email' => trans('auth.failed')];

        $this->post('/login', ['email' => $this->user->email, 'password' => 'errada'])->assertSessionHasErrors($answer);
        $this->post('/login', ['email' => 'ninguem@example.com', 'password' => 'errada'])->assertSessionHasErrors($answer);

        Notification::assertNothingSent();
    }

    public function test_only_the_latest_code_of_two_logins_is_valid(): void
    {
        $this->logIn();
        $first = $this->sentCode();
        $firstSession = session('login.pending');

        $this->flushSession();

        $this->logIn();

        $this->withSession(['login.pending' => $firstSession])
            ->post('/login/code', ['code' => $first])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_an_account_inactivated_during_the_step_does_not_log_in(): void
    {
        $this->logIn();
        $this->user->forceFill(['is_active' => false])->save();

        $this->post('/login/code', ['code' => $this->sentCode()])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_account_deleted_during_the_step_does_not_log_in(): void
    {
        $this->logIn();
        $this->user->delete();

        $this->post('/login/code', ['code' => $this->sentCode()])->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_an_inactive_account_gets_no_new_code(): void
    {
        $this->logIn();
        $this->user->forceFill(['is_active' => false])->save();

        $this->post('/login/code/resend')->assertRedirect('/login');

        Notification::assertSentToTimes($this->user, LoginCode::class, 1);
    }

    public function test_the_mandatory_password_change_continues_after_the_code(): void
    {
        $this->user->forceFill(['must_change_password' => true])->save();

        $this->logIn();
        $this->post('/login/code', ['code' => $this->sentCode()]);

        $this->get('/dashboard')->assertRedirect(route('password.change', absolute: false));
    }

    private function remembers(TestResponse $response): bool
    {
        return collect($response->headers->getCookies())
            ->contains(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web'));
    }

    public function test_remember_me_only_applies_after_the_code(): void
    {
        $this->assertFalse($this->remembers($this->logIn(['remember' => true])));

        $this->assertTrue($this->remembers($this->post('/login/code', ['code' => $this->sentCode()])));
    }

    public function test_without_remember_me_the_session_is_not_remembered(): void
    {
        $this->logIn();

        $this->assertFalse($this->remembers($this->post('/login/code', ['code' => $this->sentCode()])));
    }

    public function test_the_code_is_not_stored_in_readable_form(): void
    {
        $this->logIn();

        $this->assertStringNotContainsString($this->sentCode(), serialize(Cache::get("login-code:{$this->user->id}")));
        $this->assertStringNotContainsString($this->sentCode(), serialize(session()->all()));
    }

    public function test_a_failed_email_does_not_finish_the_login(): void
    {
        Notification::shouldReceive('send')->andThrow(new RuntimeException('Falha no servidor de e-mail'));

        $this->logIn()->assertRedirect()->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->get('/login/code')->assertRedirect('/login');
        $this->assertNull(Cache::get("login-code:{$this->user->id}"));
    }

    public function test_the_email_carries_the_code_the_validity_and_the_system_name(): void
    {
        config(['app.name' => 'Sistema Teste']);

        $message = (new LoginCode('123456'))->toMail($this->user);

        $this->assertStringContainsString('Sistema Teste', $message->subject);
        $this->assertStringContainsString('123456', implode(' ', $message->introLines));
        $this->assertStringContainsString('15', implode(' ', $message->introLines));
    }

    public function test_turning_the_option_off_finishes_the_login_after_the_password(): void
    {
        config(['template.login.two_factor_enabled' => false]);

        $this->logIn()->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($this->user);
        Notification::assertNothingSent();
    }

    public function test_the_option_is_documented_and_on_by_default(): void
    {
        $this->assertStringContainsString('LOGIN_TWO_FACTOR_ENABLED=true', File::get(base_path('.env.example')));
        $this->assertMatchesRegularExpression("/env\('LOGIN_TWO_FACTOR_ENABLED', true\)/", File::get(config_path('template.php')));
    }
}
