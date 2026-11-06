<?php

namespace Tests\Feature\Actions;

use App\Actions\CreateUser;
use App\Models\User;
use App\Notifications\WelcomeUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    private function create(mixed ...$arguments): User
    {
        return app(CreateUser::class)->handle('Ana', 'ana@example.com', ...$arguments);
    }

    public function test_creates_unconfirmed_user_with_flag_and_temporary_password(): void
    {
        Notification::fake();
        $this->freezeSecond();

        $user = $this->create();

        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->temporary_password_expires_at->equalTo(now()->addMinutes(60)));

        Notification::assertSentTo($user, WelcomeUser::class, function (WelcomeUser $notification) use ($user) {
            return Hash::check($notification->temporaryPassword, $user->password);
        });
    }

    public function test_email_has_access_data_validity_and_link(): void
    {
        Notification::fake();

        $user = $this->create('senha-temporaria');

        Notification::assertSentTo($user, WelcomeUser::class, function (WelcomeUser $notification) use ($user) {
            $mail = $notification->toMail($user);
            $text = implode(' ', $mail->introLines);

            return str_contains($text, 'ana@example.com')
                && str_contains($text, 'senha-temporaria')
                && str_contains($text, '60')
                && str_contains($mail->actionUrl, '/verify-email/');
        });
    }

    public function test_omits_password_when_chosen_by_the_user(): void
    {
        Notification::fake();

        $user = $this->create('Senha-escolhida-1', temporary: false);

        $this->assertNull($user->temporary_password_expires_at);
        $this->assertTrue($user->must_change_password);

        Notification::assertSentTo($user, WelcomeUser::class, function (WelcomeUser $notification) use ($user) {
            return $notification->temporaryPassword === null
                && ! str_contains(implode(' ', $notification->toMail($user)->introLines), 'Senha-escolhida-1');
        });
    }

    public function test_verified_user_is_confirmed_and_receives_no_email(): void
    {
        Notification::fake();
        $this->freezeSecond();

        $user = app(CreateUser::class)->verified('Ana', 'ana@example.com', 'Senha-escolhida-1');

        $this->assertTrue($user->fresh()->email_verified_at->equalTo(now()));
        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertNull($user->temporary_password_expires_at);
        $this->assertTrue(Hash::check('Senha-escolhida-1', $user->password));

        Notification::assertNothingSent();
    }

    public function test_send_failure_is_informed_to_the_caller(): void
    {
        Exceptions::fake();
        Notification::shouldReceive('send')->andThrow(new RuntimeException('Falha no servidor de e-mail'));

        $this->assertTrue($this->create()->emailFailed);
    }

    public function test_successful_send_is_not_flagged(): void
    {
        Notification::fake();

        $this->assertFalse($this->create()->emailFailed);
    }

    public function test_email_of_a_deleted_account_is_informed_as_reactivation(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'ana@example.com'])->delete();

        $user = $this->create();

        $this->assertTrue($user->wasReactivated);
        $this->assertSame(1, User::withTrashed()->count());
    }

    public function test_send_failure_keeps_the_user_and_is_reported(): void
    {
        Exceptions::fake();
        Notification::shouldReceive('send')->andThrow(new RuntimeException('Falha no servidor de e-mail'));

        $this->create('senha-temporaria');

        $this->assertDatabaseHas('users', ['email' => 'ana@example.com']);

        Exceptions::assertReported(fn (RuntimeException $e) => ! str_contains($e->getMessage(), 'senha-temporaria'));
    }
}
