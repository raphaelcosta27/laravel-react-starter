<?php

namespace Tests\Feature;

use App\Actions\DeleteExpiredLogs;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CleanLogsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/clean-logs-'.uniqid();

        File::ensureDirectoryExists($this->directory);

        config([
            'logging.channels.daily.path' => "{$this->directory}/laravel.log",
            'template.logs.retention_days' => 3,
        ]);

        $this->travelTo('2026-11-10 12:00:00');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    private function file(string $name): string
    {
        $path = "{$this->directory}/{$name}";

        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'registro');

        return $path;
    }

    /** @return array{deleted: list<string>, failed: list<string>, retentionDays: int, invalidRetention: bool} */
    private function clean(): array
    {
        return app(DeleteExpiredLogs::class)->handle();
    }

    public function test_only_files_above_the_retention_are_deleted(): void
    {
        $expired = $this->file('laravel-2026-11-06.log');
        $limit = $this->file('laravel-2026-11-07.log');
        $today = $this->file('laravel-2026-11-10.log');
        $future = $this->file('laravel-2026-11-11.log');

        $result = $this->clean();

        $this->assertSame(['laravel-2026-11-06.log'], $result['deleted']);
        $this->assertFileDoesNotExist($expired);
        $this->assertFileExists($limit);
        $this->assertFileExists($today);
        $this->assertFileExists($future);
    }

    public function test_files_outside_the_log_pattern_are_kept(): void
    {
        $kept = array_map($this->file(...), [
            'laravel.log',
            'laravel-2026-13-45.log',
            'laravel-2020-02-30.log',
            'laravel-backup.log',
            'laravel-2020-01-01.log.bak',
            'outro-2020-01-01.log',
            '.gitignore',
            'arquivados/laravel-2020-01-01.log',
        ]);

        $this->assertSame([], $this->clean()['deleted']);

        foreach ($kept as $path) {
            $this->assertFileExists($path);
        }
    }

    public function test_name_and_folder_come_from_the_daily_channel(): void
    {
        config(['logging.channels.daily.path' => "{$this->directory}/app.log"]);

        $expired = $this->file('app-2020-01-01.log');
        $other = $this->file('laravel-2020-01-01.log');

        $this->clean();

        $this->assertFileDoesNotExist($expired);
        $this->assertFileExists($other);
    }

    /** @return array<string, array{mixed, int}> */
    public static function validRetentions(): array
    {
        return [
            'ausente' => [null, 14],
            'vazio' => ['', 14],
            'inteiro' => [7, 7],
            'texto' => ['7', 7],
        ];
    }

    #[DataProvider('validRetentions')]
    public function test_valid_retention_values(mixed $value, int $expected): void
    {
        config(['template.logs.retention_days' => $value]);

        Log::spy();

        $result = $this->clean();

        $this->assertSame($expected, $result['retentionDays']);
        $this->assertFalse($result['invalidRetention']);
        Log::shouldNotHaveReceived('warning');
    }

    /** @return array<string, array{mixed}> */
    public static function invalidRetentions(): array
    {
        return [
            'zero' => [0],
            'zero em texto' => ['0'],
            'negativo' => [-1],
            'letras' => ['abc'],
            'número com letras' => ['7dias'],
            'decimal' => ['1.5'],
            'booleano' => [true],
        ];
    }

    #[DataProvider('invalidRetentions')]
    public function test_invalid_retention_uses_the_default_and_warns(mixed $value): void
    {
        config(['template.logs.retention_days' => $value]);

        Log::spy();

        $inside = $this->file('laravel-2026-10-27.log');
        $expired = $this->file('laravel-2026-10-26.log');

        $result = $this->clean();

        $this->assertSame(14, $result['retentionDays']);
        $this->assertTrue($result['invalidRetention']);
        $this->assertFileExists($inside);
        $this->assertFileDoesNotExist($expired);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_a_failure_does_not_stop_the_other_files(): void
    {
        $locked = $this->file('laravel-2020-01-01.log');
        $other = $this->file('laravel-2020-01-02.log');

        Log::spy();

        File::partialMock()->shouldReceive('delete')->andReturnUsing(
            fn (string $path) => basename($path) === basename($locked) ? false : unlink($path),
        );

        $result = $this->clean();

        $this->assertSame(['laravel-2020-01-01.log'], $result['failed']);
        $this->assertSame(['laravel-2020-01-02.log'], $result['deleted']);
        $this->assertFileDoesNotExist($other);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_the_command_fails_when_a_file_could_not_be_deleted(): void
    {
        $this->file('laravel-2020-01-01.log');

        File::partialMock()->shouldReceive('delete')->andReturnFalse();

        $this->artisan('app:clean-logs')
            ->expectsOutputToContain('laravel-2020-01-01.log')
            ->assertExitCode(1);
    }

    public function test_a_file_that_vanished_is_not_a_failure(): void
    {
        $this->file('laravel-2020-01-01.log');

        File::partialMock()->shouldReceive('delete')->andReturnUsing(function (string $path) {
            unlink($path);

            return false;
        });

        $this->assertSame([], $this->clean()['failed']);
    }

    public function test_a_missing_folder_is_not_a_failure(): void
    {
        config(['logging.channels.daily.path' => "{$this->directory}/nao-existe/laravel.log"]);

        $this->artisan('app:clean-logs')->assertExitCode(0);
    }

    public function test_the_command_reports_the_result(): void
    {
        $this->file('laravel-2020-01-01.log');

        $this->artisan('app:clean-logs')
            ->expectsOutputToContain(__(':count log file(s) deleted. Retention: :days days.', ['count' => 1, 'days' => 3]))
            ->assertExitCode(0);
    }

    public function test_the_command_warns_about_an_invalid_retention(): void
    {
        config(['template.logs.retention_days' => 'abc']);

        $this->artisan('app:clean-logs')
            ->expectsOutputToContain(__('Invalid LOG_DAILY_DAYS value. Using the default retention.'))
            ->assertExitCode(0);
    }

    public function test_the_clean_command_runs_every_day(): void
    {
        Artisan::call('schedule:list');

        $this->assertMatchesRegularExpression('/0\s+0\s+\*\s+\*\s+\*\s+php artisan app:clean-logs/', Artisan::output());
    }
}
