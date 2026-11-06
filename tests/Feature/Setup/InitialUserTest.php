<?php

namespace Tests\Feature\Setup;

use App\Models\User;
use App\Notifications\WelcomeUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InitialUserTest extends TestCase
{
    use RefreshDatabase;

    private string $environmentPath;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->environmentPath = storage_path('framework/testing/env-'.uniqid());
        File::ensureDirectoryExists($this->environmentPath);
        File::put($this->environmentPath.'/.env', 'APP_KEY='.config('app.key')."\n");

        $this->app->useEnvironmentPath($this->environmentPath);

        config([
            'template.initial_user.name' => 'Administrador',
            'template.initial_user.email' => 'admin@example.com',
            'template.initial_user.password' => 'Senha-inicial-1',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->environmentPath);

        parent::tearDown();
    }

    public function test_creates_the_initial_user_and_sends_the_email(): void
    {
        $this->artisan('app:setup')->assertSuccessful();

        $user = User::firstWhere('email', 'admin@example.com');

        $this->assertTrue(Hash::check('Senha-inicial-1', $user->password));
        $this->assertTrue($user->must_change_password);

        Notification::assertSentTo($user, WelcomeUser::class);
    }

    public function test_refuses_a_weak_initial_password_without_creating_the_user(): void
    {
        config(['template.initial_user.password' => 'fraca']);

        $this->artisan('app:setup')
            ->expectsOutputToContain('INITIAL_USER_PASSWORD')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
        Notification::assertNothingSent();
    }

    public function test_repeating_keeps_the_user_and_password(): void
    {
        $this->artisan('app:setup')->assertSuccessful();
        $password = User::first()->password;

        config(['template.initial_user.password' => 'Outra-senha-1']);

        $this->artisan('app:setup')->assertSuccessful();

        $this->assertSame(1, User::count());
        $this->assertSame($password, User::first()->password);

        Notification::assertSentTimes(WelcomeUser::class, 1);
    }

    public function test_empty_password_generates_one(): void
    {
        config(['template.initial_user.password' => '']);

        $this->artisan('app:setup')->assertSuccessful();

        $user = User::first();

        Notification::assertSentTo($user, WelcomeUser::class, function (WelcomeUser $notification) use ($user) {
            return strlen($notification->temporaryPassword) >= 8
                && Hash::check($notification->temporaryPassword, $user->password);
        });
    }

    public function test_empty_email_finishes_without_creating_the_user(): void
    {
        config(['template.initial_user.email' => '']);

        $this->artisan('app:setup')
            ->expectsOutputToContain('INITIAL_USER_EMAIL')
            ->assertSuccessful();

        $this->assertSame(0, User::count());
    }
}
