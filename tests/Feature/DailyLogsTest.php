<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DailyLogsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/daily-logs-'.uniqid();

        File::ensureDirectoryExists($this->directory);

        config(['logging.channels.daily.path' => "{$this->directory}/laravel.log"]);
    }

    protected function tearDown(): void
    {
        Log::forgetChannel('daily');

        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_daily_is_the_default_log_destination(): void
    {
        $this->assertStringContainsString("env('LOG_STACK', 'daily')", File::get(config_path('logging.php')));
        $this->assertStringContainsString("\nLOG_STACK=daily\n", File::get(base_path('.env.example')));
    }

    public function test_the_retention_is_left_to_the_clean_command(): void
    {
        $this->assertSame(0, config('logging.channels.daily.max_files'));
    }

    public function test_concurrent_writes_are_locked(): void
    {
        $this->assertTrue(config('logging.channels.daily.locking'));
    }

    public function test_a_record_goes_to_the_file_of_the_day(): void
    {
        $this->assertSame([], File::files($this->directory));

        Log::channel('daily')->info('mensagem completa');

        $file = "{$this->directory}/laravel-".date('Y-m-d').'.log';

        $this->assertFileExists($file);
        $this->assertStringContainsString('mensagem completa', File::get($file));
    }

    public function test_the_example_file_points_emails_to_the_daily_file(): void
    {
        $example = File::get(base_path('.env.example'));

        $this->assertStringNotContainsString('storage/logs/laravel.log', $example);
        $this->assertStringContainsString('storage/logs/laravel-AAAA-MM-DD.log', $example);
    }
}
