<?php

namespace Tests\Feature\Install;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Trabalha numa pasta temporária: o arquivo de ambiente real nunca é tocado.
 */
abstract class EnvironmentTestCase extends TestCase
{
    use RefreshDatabase;

    protected string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'template-env-'.uniqid();

        File::ensureDirectoryExists($this->directory);

        app()->useEnvironmentPath($this->directory);
        app()->loadEnvironmentFrom('.env');

        config(['template.install.directory' => $this->directory.DIRECTORY_SEPARATOR.'system']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    protected function env(string $contents): void
    {
        File::put($this->envPath(), $contents);
    }

    protected function envPath(): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.'.env';
    }

    protected function envContents(): string
    {
        return (string) file_get_contents($this->envPath());
    }
}
