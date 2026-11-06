<?php

namespace Tests\Feature\Auth;

use App\Actions\IssueTemporaryPassword;
use App\Models\User;
use App\Notifications\WelcomeUser;
use App\Support\Auth\PasswordCriteria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordStrengthTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG = 'Senha-Forte-1';

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('en');
        config(['template.registration_enabled' => true]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function weakPasswords(): array
    {
        return [
            'sem tamanho' => ['Ab1!', 'at least 8 characters'],
            'sem maiúscula' => ['senha-forte-1', 'one uppercase letter'],
            'sem minúscula' => ['SENHA-FORTE-1', 'one lowercase letter'],
            'sem número' => ['Senha-Forte', 'one number'],
            'sem especial' => ['SenhaForte1', 'one special character'],
        ];
    }

    #[DataProvider('weakPasswords')]
    public function test_registration_refuses_a_weak_password(string $password, string $missing): void
    {
        $this->post('/register', [
            'name' => 'Pessoa',
            'email' => 'pessoa@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertSessionHasErrors('password');

        $this->assertMessageNames($missing);
        $this->assertDatabaseMissing('users', ['email' => 'pessoa@example.com']);
    }

    #[DataProvider('weakPasswords')]
    public function test_voluntary_change_refuses_a_weak_password(string $password, string $missing): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertSessionHasErrors('password');

        $this->assertMessageNames($missing);
        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    #[DataProvider('weakPasswords')]
    public function test_required_change_refuses_a_weak_password(string $password, string $missing): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertSessionHasErrors('password');

        $this->assertMessageNames($missing);
        $this->assertTrue($user->refresh()->must_change_password);
    }

    #[DataProvider('weakPasswords')]
    public function test_reset_refuses_a_weak_password(string $password, string $missing): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertSessionHasErrors('password');

        $this->assertMessageNames($missing);
        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_a_password_with_every_criterion_is_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password',
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check(self::STRONG, $user->refresh()->password));
    }

    public function test_the_message_lists_everything_that_is_missing(): void
    {
        $message = (string) $this->failureFor('abc');

        foreach (['at least 8 characters', 'one uppercase letter', 'one number', 'one special character'] as $missing) {
            $this->assertStringContainsString($missing, $message);
        }

        $this->assertStringNotContainsString('lowercase', $message);
    }

    public function test_the_message_is_translated(): void
    {
        app()->setLocale('pt_BR');

        $this->assertStringContainsString('uma letra maiúscula', (string) $this->failureFor('senha-forte-1'));
    }

    public function test_a_password_above_the_limit_gets_a_clear_message(): void
    {
        $this->assertStringContainsString('72', (string) $this->failureFor('Aa1!'.str_repeat('a', 69)));
    }

    public function test_a_password_at_the_limit_is_accepted(): void
    {
        $this->assertNull($this->failureFor('Aa1!'.str_repeat('a', 68)));
    }

    public function test_a_value_that_is_not_text_is_refused_without_breaking(): void
    {
        $this->post('/register', [
            'name' => 'Pessoa',
            'email' => 'pessoa@example.com',
            'password' => [self::STRONG],
            'password_confirmation' => [self::STRONG],
        ])->assertSessionHasErrors('password');
    }

    public function test_the_minimum_length_can_be_raised(): void
    {
        config(['template.password.min_length' => 12]);

        $this->assertStringContainsString('at least 12 characters', (string) $this->failureFor('Senha-Fort1'));
        $this->assertNull($this->failureFor('Senha-Forte-12'));
    }

    public function test_the_minimum_length_never_goes_below_eight(): void
    {
        config(['template.password.min_length' => 4]);

        $this->assertStringContainsString('at least 8 characters', (string) $this->failureFor('Ab1!'));
    }

    public function test_the_minimum_length_never_goes_above_the_limit(): void
    {
        config(['template.password.min_length' => 100]);

        $this->assertNull($this->failureFor('Aa1!'.str_repeat('a', 68)));
    }

    public function test_generated_passwords_meet_every_criterion(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $this->assertNull($this->failureFor(PasswordCriteria::generate()));
        }
    }

    public function test_generated_passwords_follow_a_raised_minimum_length(): void
    {
        config(['template.password.min_length' => 20]);

        $this->assertSame(20, strlen(PasswordCriteria::generate()));
    }

    public function test_the_temporary_password_meets_every_criterion_and_arrives_intact(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        app(IssueTemporaryPassword::class)->handle($user);

        Notification::assertSentTo($user, WelcomeUser::class, function (WelcomeUser $notification) use ($user) {
            $this->assertNull($this->failureFor($notification->temporaryPassword));
            $this->assertStringContainsString(
                $notification->temporaryPassword,
                (string) $notification->toMail($user)->render(),
            );

            return true;
        });
    }

    public function test_people_with_an_old_weak_password_can_still_log_in(): void
    {
        $user = User::factory()->create(['password' => 'fraca']);

        $this->post('/login', ['email' => $user->email, 'password' => 'fraca']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_pages_receive_the_criteria_used_by_the_server(): void
    {
        $this->get('/register')->assertInertia(fn (Assert $page) => $page
            ->where('passwordCriteria', $this->fixture()['criteria'])
            ->etc());
    }

    public function test_the_criteria_match_the_samples_shared_with_the_browser(): void
    {
        foreach ($this->fixture()['samples'] as $sample) {
            $met = array_filter(
                PasswordCriteria::all(),
                fn (array $criterion) => PasswordCriteria::meets($criterion, $sample['password']),
            );

            $this->assertSame($sample['met'], array_column($met, 'key'), $sample['password']);
        }
    }

    /**
     * @return array{criteria: array<int, array<string, string>>, samples: array<int, array<string, mixed>>}
     */
    private function fixture(): array
    {
        return json_decode((string) file_get_contents(base_path('tests/fixtures/password-criteria.json')), true);
    }

    private function assertMessageNames(string $missing): void
    {
        $this->assertStringContainsString($missing, (string) $this->lastPasswordError());
    }

    private function lastPasswordError(): ?string
    {
        $errors = session('errors');

        return is_array($errors) ? ($errors['password'] ?? null) : $errors?->first('password');
    }

    private function failureFor(string $password): ?string
    {
        return Validator::make(['password' => $password], ['password' => [PasswordRule::defaults()]])
            ->errors()
            ->first('password') ?: null;
    }
}
