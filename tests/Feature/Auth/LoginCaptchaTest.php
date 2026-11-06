<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoginCaptchaTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY_URL = 'challenges.cloudflare.com/turnstile/v0/siteverify';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.turnstile' => ['enabled' => true, 'site_key' => 'chave-site', 'secret_key' => 'chave-secreta']]);

        $this->user = User::factory()->create();
    }

    private function logIn(array $extra = [], string $password = 'password'): TestResponse
    {
        return $this->post('/login', ['email' => $this->user->email, 'password' => $password, ...$extra]);
    }

    public function test_the_login_is_refused_without_the_proof(): void
    {
        Http::fake();

        $this->logIn()->assertSessionHasErrors('captcha');

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_an_invalid_proof_is_refused(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(['success' => false])]);

        $this->logIn(['captcha' => 'token'])->assertSessionHasErrors('captcha');

        $this->assertGuest();
    }

    public function test_a_valid_proof_continues_the_login(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(['success' => true])]);

        $this->logIn(['captcha' => 'token'])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($this->user);

        Http::assertSent(fn (Request $request) => $request['secret'] === 'chave-secreta' && $request['response'] === 'token');
    }

    public function test_the_credentials_are_not_evaluated_without_a_valid_proof(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(['success' => false])]);

        $this->logIn(['captcha' => 'token'], 'errada')
            ->assertSessionHasErrors('captcha')
            ->assertSessionDoesntHaveErrors('email');
    }

    public function test_an_unavailable_service_refuses_the_login_with_a_clear_message(): void
    {
        Http::fake(fn () => throw new ConnectionException('Fora do ar'));

        $this->logIn(['captcha' => 'token'])
            ->assertSessionHasErrors(['captcha' => __('The verification service is unavailable. Try again later.')]);

        $this->assertGuest();
    }

    public function test_a_service_error_refuses_the_login(): void
    {
        Http::fake([self::VERIFY_URL => Http::response('erro', 500)]);

        $this->logIn(['captcha' => 'token'])->assertSessionHasErrors('captcha');

        $this->assertGuest();
    }

    public function test_turned_off_it_is_neither_offered_nor_required(): void
    {
        config(['services.turnstile.enabled' => false]);
        Http::fake();

        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('captchaSiteKey', null));

        $this->logIn()->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($this->user);
        Http::assertNothingSent();
    }

    public function test_the_login_screen_receives_only_the_public_key(): void
    {
        $response = $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('captchaSiteKey', 'chave-site'));

        $this->assertStringNotContainsString('chave-secreta', $response->getContent());
    }

    public function test_the_keys_are_documented_and_off_by_default(): void
    {
        $example = File::get(base_path('.env.example'));

        $this->assertStringContainsString('TURNSTILE_ENABLED=false', $example);
        $this->assertStringContainsString('TURNSTILE_SITE_KEY=', $example);
        $this->assertStringContainsString('TURNSTILE_SECRET_KEY=', $example);
    }
}
