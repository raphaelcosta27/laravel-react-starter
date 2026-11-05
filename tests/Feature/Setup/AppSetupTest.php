<?php

namespace Tests\Feature\Setup;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppSetupTest extends TestCase
{
    private string $environmentPath;

    protected function setUp(): void
    {
        parent::setUp();

        config(['template.initial_user.email' => null]);

        $this->environmentPath = storage_path('framework/testing/env-'.uniqid());
        File::ensureDirectoryExists($this->environmentPath);
        File::put($this->environmentPath.'/.env', "APP_KEY=\n");

        $this->app->useEnvironmentPath($this->environmentPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->environmentPath);

        parent::tearDown();
    }

    public function test_applies_migrations(): void
    {
        Artisan::call('db:wipe', ['--force' => true]);

        $this->artisan('app:setup')->assertSuccessful();

        $this->assertTrue(Schema::hasTable('users'));
    }

    public function test_repeating_keeps_existing_data(): void
    {
        $this->artisan('app:setup')->assertSuccessful();
        $user = User::factory()->create();

        $this->artisan('app:setup')->assertSuccessful();
        $this->artisan('app:setup')->assertSuccessful();
        $this->artisan('app:setup')->assertSuccessful();

        $this->assertSame(1, User::count());
        $this->assertTrue(User::first()->is($user));
    }

    public function test_generates_key_when_empty(): void
    {
        config(['app.key' => '']);

        $this->artisan('app:setup')->assertSuccessful();

        $this->assertStringContainsString('APP_KEY=base64:', File::get($this->environmentPath.'/.env'));
    }

    public function test_keeps_existing_key(): void
    {
        $key = config('app.key');
        File::put($this->environmentPath.'/.env', "APP_KEY={$key}\n");

        $this->artisan('app:setup')->assertSuccessful();

        $this->assertSame("APP_KEY={$key}\n", File::get($this->environmentPath.'/.env'));
    }

    public function test_fails_with_guidance_when_env_file_is_missing(): void
    {
        File::delete($this->environmentPath.'/.env');

        $this->artisan('app:setup')
            ->expectsOutputToContain('.env.example')
            ->assertFailed();
    }

    public function test_migration_failure_is_not_reported_as_connection_failure(): void
    {
        Artisan::call('db:wipe', ['--force' => true]);
        Schema::create('users', fn ($table) => $table->id());

        $this->artisan('app:setup')
            ->expectsOutputToContain('already exists')
            ->doesntExpectOutputToContain('DB_HOST')
            ->assertFailed();
    }

    public function test_connection_failure_shows_guidance_without_secrets(): void
    {
        $default = config('database.default');

        config([
            'database.default' => 'unreachable',
            'database.connections.unreachable' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 1,
                'database' => 'template',
                'username' => 'root',
                'password' => 'segredo-de-teste',
            ],
        ]);

        DB::purge('unreachable');

        $this->artisan('app:setup')
            ->expectsOutputToContain('DB_HOST')
            ->doesntExpectOutputToContain('segredo-de-teste')
            ->assertFailed();

        config(['database.default' => $default]);
    }
}
