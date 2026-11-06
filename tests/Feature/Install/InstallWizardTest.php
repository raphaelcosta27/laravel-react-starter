<?php

namespace Tests\Feature\Install;

use App\Models\User;
use App\Support\Install\Installation;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

class InstallWizardTest extends EnvironmentTestCase
{
    private const PASSWORD = 'Senha-Forte-123!';

    protected function setUp(): void
    {
        parent::setUp();

        config(['template.install.enabled' => true]);

        $this->env("APP_NAME=Template\nDB_CONNECTION=sqlite\nDB_DATABASE=:memory:\n");
    }

    private function installation(): Installation
    {
        return app(Installation::class);
    }

    private function unlock(): void
    {
        $this->post('/install/unlock', ['code' => $this->installation()->code()])->assertSessionHasNoErrors();
    }

    private function untilDatabase(): void
    {
        $this->unlock();
        $this->post('/install/environment', ['values' => ['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:']])
            ->assertSessionHasNoErrors();
        $this->post('/install/database')->assertSessionHasNoErrors();
    }

    /**
     * @return array<string, string>
     */
    private function person(): array
    {
        return [
            'name' => 'Ana Lima',
            'email' => 'ana@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ];
    }

    private function finish(): void
    {
        $this->untilDatabase();
        $this->post('/install/user', $this->person())->assertSessionHasNoErrors();
    }

    public function test_every_page_leads_to_the_wizard_while_there_are_no_users(): void
    {
        foreach (['/', '/login', '/register', '/dashboard', '/admin/users'] as $path) {
            $this->get($path)->assertRedirect('/install');
        }
    }

    public function test_the_public_registration_is_closed_before_the_installation(): void
    {
        config(['template.accounts.registration_enabled' => true]);

        $this->post('/register', $this->person())->assertRedirect('/install');

        $this->assertSame(0, User::count());
    }

    public function test_the_health_check_stays_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_the_wizard_starts_by_asking_for_the_code_and_reveals_nothing_else(): void
    {
        $this->get('/install')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Install/Index')
            ->where('step', 'unlock')
            ->where('groups', [])
            ->where('codePath', 'storage/app/private/system/install-code.txt'));
    }

    public function test_the_code_is_generated_in_a_file_and_in_the_log(): void
    {
        Log::spy();

        $code = $this->installation()->code();

        $this->assertGreaterThanOrEqual(32, strlen($code));
        $this->assertSame($code, trim((string) file_get_contents($this->installation()->codePath())));
        $this->assertSame($code, $this->installation()->code());

        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message) => str_contains($message, $code));
    }

    public function test_a_wrong_code_is_refused(): void
    {
        $this->post('/install/unlock', ['code' => 'errado'])->assertSessionHasErrors('code');

        $this->get('/install')->assertInertia(fn (Assert $page) => $page->where('step', 'unlock'));
    }

    public function test_wrong_codes_are_limited(): void
    {
        foreach (range(1, 5) as $ignored) {
            $this->post('/install/unlock', ['code' => 'errado']);
        }

        $this->post('/install/unlock', ['code' => $this->installation()->code()])->assertTooManyRequests();
    }

    public function test_nothing_runs_without_the_code(): void
    {
        $this->post('/install/environment', ['values' => ['APP_NAME' => 'Invasor']])->assertForbidden();
        $this->post('/install/database')->assertForbidden();
        $this->post('/install/user', $this->person())->assertForbidden();

        $this->assertStringContainsString('APP_NAME=Template', $this->envContents());
        $this->assertSame(0, User::count());
    }

    public function test_a_new_code_invalidates_the_previous_unlock(): void
    {
        $this->unlock();

        Artisan::call('app:install-code');

        $this->post('/install/environment', ['values' => []])->assertForbidden();
    }

    public function test_the_code_shows_the_environment_step(): void
    {
        $this->unlock();

        $this->get('/install')->assertInertia(fn (Assert $page) => $page
            ->where('step', 'environment')
            ->has('groups.0.parameters'));
    }

    public function test_invalid_database_data_shows_the_error_without_the_password(): void
    {
        $this->unlock();

        $this->post('/install/environment', ['values' => [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '1',
            'DB_PASSWORD' => 'senha-digitada',
        ]])->assertSessionHasErrors('values.DB_HOST');

        $this->assertStringNotContainsString('senha-digitada', session('errors')->first('values.DB_HOST'));
        $this->assertStringNotContainsString('senha-digitada', $this->envContents());

        $this->get('/install')->assertInertia(fn (Assert $page) => $page->where('step', 'environment'));
    }

    public function test_the_steps_run_in_order(): void
    {
        $this->unlock();

        $this->post('/install/database')->assertConflict();
        $this->post('/install/user', $this->person())->assertConflict();
    }

    public function test_the_database_is_prepared_and_can_be_repeated(): void
    {
        $this->untilDatabase();

        $this->post('/install/database')->assertSessionHasNoErrors();

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertDatabaseHas('permissions', ['code' => 'configura_env']);

        $this->get('/install')->assertInertia(fn (Assert $page) => $page->where('step', 'user'));
    }

    public function test_a_weak_password_is_refused(): void
    {
        $this->untilDatabase();

        $this->post('/install/user', [...$this->person(), 'password' => 'fraca', 'password_confirmation' => 'fraca'])
            ->assertSessionHasErrors('password');

        $this->assertSame(0, User::count());
    }

    public function test_the_first_user_is_a_ready_administrator(): void
    {
        Notification::fake();

        $this->finish();

        $user = User::firstWhere('email', 'ana@example.com');

        $this->assertTrue($user->isAdministrator());
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse((bool) $user->must_change_password);
        Notification::assertNothingSent();
    }

    public function test_the_person_enters_authenticated_by_a_single_use_address(): void
    {
        $this->untilDatabase();

        $location = $this->post('/install/user', $this->person())->headers->get('Location');

        $this->assertGuest();

        $this->get($location)->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $this->post('/logout');

        $this->get($location)->assertNotFound();
        $this->assertGuest();
    }

    public function test_a_wrong_token_does_not_log_in(): void
    {
        $this->finish();

        $this->get('/install/finish/errado')->assertNotFound();
        $this->assertGuest();
    }

    public function test_an_old_token_does_not_log_in(): void
    {
        $this->untilDatabase();

        $location = $this->post('/install/user', $this->person())->headers->get('Location');

        $this->travel(11)->minutes();

        $this->get($location)->assertNotFound();
    }

    public function test_the_wizard_is_gone_after_the_installation(): void
    {
        $this->finish();

        $this->assertFileDoesNotExist($this->installation()->codePath());

        $this->get('/install')->assertNotFound();
        $this->post('/install/unlock', ['code' => 'x'])->assertNotFound();
        $this->post('/install/environment', ['values' => []])->assertNotFound();
        $this->post('/install/database')->assertNotFound();
        $this->post('/install/user', $this->person())->assertNotFound();
    }

    public function test_a_user_created_by_command_hides_the_wizard(): void
    {
        User::factory()->create();

        $this->get('/login')->assertOk();
        $this->get('/install')->assertNotFound();
    }

    public function test_a_deleted_user_also_hides_the_wizard(): void
    {
        User::factory()->create()->delete();

        $this->get('/install')->assertNotFound();
    }

    public function test_a_database_failure_does_not_reopen_an_installed_system(): void
    {
        User::factory()->create();
        $this->get('/login');

        $connection = config('database.default');

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => 1,
        ]);

        try {
            $this->assertTrue($this->installation()->isInstalled());
        } finally {
            config(['database.default' => $connection]);
        }
    }

    public function test_a_missing_file_is_created_from_the_example(): void
    {
        File::delete($this->envPath());

        $this->get('/install')->assertOk();

        $this->assertFileExists($this->envPath());
        $this->assertStringContainsString('# Aplicação', $this->envContents());
    }

    public function test_a_missing_key_is_generated(): void
    {
        config(['app.key' => '']);

        $this->get('/install')->assertOk();

        $this->assertMatchesRegularExpression('/^APP_KEY=base64:.{40,}$/m', $this->envContents());
    }

    public function test_the_wizard_works_with_the_session_in_a_file(): void
    {
        config(['session.driver' => 'database', 'cache.default' => 'database']);

        $this->get('/login')->assertRedirect('/install');

        $this->assertSame('file', config('session.driver'));
        $this->assertSame('file', config('cache.default'));
    }

    public function test_the_command_refuses_an_installed_system(): void
    {
        User::factory()->create();

        $this->artisan('app:install-code')->assertFailed();
    }

    public function test_the_wizard_can_be_turned_off(): void
    {
        config(['template.install.enabled' => false]);

        $this->get('/login')->assertOk();
        $this->get('/install')->assertNotFound();
    }
}
