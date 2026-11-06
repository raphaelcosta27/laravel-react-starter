<?php

namespace Tests\Feature;

use Tests\TestCase;

class DockerFilesTest extends TestCase
{
    private const OFFICIAL = ['php', 'composer', 'mysql', 'node', 'redis'];

    private function compose(): string
    {
        return (string) file_get_contents(base_path('docker/docker-compose.yml'));
    }

    private function dockerfile(): string
    {
        return (string) file_get_contents(base_path('docker/php/Dockerfile'));
    }

    /**
     * @return array<int, string>
     */
    private function images(): array
    {
        preg_match_all('/^\s*image:\s*(\S+)/m', $this->compose(), $compose);
        preg_match_all('/^FROM\s+(\S+)/m', $this->dockerfile(), $dockerfile);

        return array_values(array_filter(
            [...$compose[1], ...$dockerfile[1]],
            fn (string $image) => $image !== 'template-app:local',
        ));
    }

    public function test_the_project_root_is_mounted_with_a_relative_path(): void
    {
        $this->assertStringContainsString('- ../:/var/www/html', $this->compose());
    }

    public function test_only_official_images_with_a_fixed_version_are_used(): void
    {
        $images = $this->images();

        $this->assertNotEmpty($images);

        foreach ($images as $image) {
            [$name, $tag] = explode(':', $image) + [null, null];

            $this->assertContains($name, self::OFFICIAL, "{$image} não é oficial.");
            $this->assertNotNull($tag, "{$image} sem versão.");
            $this->assertNotSame('latest', $tag);
        }
    }

    public function test_the_php_version_is_declared_in_one_place(): void
    {
        $this->assertSame(1, preg_match_all('/^ARG PHP_VERSION=\d+\.\d+$/m', $this->dockerfile()));
        $this->assertStringContainsString('FROM php:${PHP_VERSION}-apache', $this->dockerfile());
    }

    public function test_the_extensions_required_by_composer_are_installed(): void
    {
        $require = json_decode((string) file_get_contents(base_path('composer.json')), true)['require'];
        $extensions = array_map(fn (string $name) => substr($name, 4), array_filter(array_keys($require), fn (string $name) => str_starts_with($name, 'ext-')));

        foreach (array_diff($extensions, ['pdo_sqlite']) as $extension) {
            $this->assertStringContainsString($extension, $this->dockerfile());
        }
    }

    public function test_mysql_is_optional_and_not_exposed(): void
    {
        preg_match('/^  mysql:\n(.*?)(?=^  \S|^\S)/ms', $this->compose(), $service);

        $this->assertStringContainsString('profiles: [mysql]', $service[1]);
        $this->assertStringContainsString('healthcheck:', $service[1]);
        $this->assertStringNotContainsString('ports:', $service[1]);
        $this->assertStringContainsString('required: false', $this->compose());
    }

    public function test_every_variable_is_documented_with_a_default(): void
    {
        $example = (string) file_get_contents(base_path('docker/.env.example'));

        preg_match_all('/\$\{(DOCKER_\w+)(:-[^}]*)?\}/', $this->compose(), $variables, PREG_SET_ORDER);

        $this->assertNotEmpty($variables);

        foreach ($variables as $variable) {
            $this->assertStringContainsString("{$variable[1]}=", $example);
            $this->assertArrayHasKey(2, $variable, "{$variable[1]} sem padrão.");
        }
    }

    public function test_the_build_context_keeps_the_project_out_of_the_image(): void
    {
        $this->assertStringContainsString('context: .', $this->compose());
        $this->assertStringNotContainsString('COPY ..', $this->dockerfile());
    }

    public function test_certificates_and_docker_env_stay_out_of_the_repository(): void
    {
        $ignored = (string) file_get_contents(base_path('.gitignore'));

        $this->assertStringContainsString('/docker/certs/*', $ignored);
        $this->assertStringContainsString('.env', $ignored);
    }

    public function test_the_application_key_exists_before_the_health_check(): void
    {
        $entrypoint = (string) file_get_contents(base_path('docker/php/entrypoint.sh'));

        $this->assertStringContainsString('artisan key:generate', $entrypoint);
        $this->assertLessThan(strpos($entrypoint, 'exec "$@"'), strpos($entrypoint, 'artisan key:generate'));
    }

    public function test_the_shell_script_uses_unix_line_endings(): void
    {
        $this->assertStringNotContainsString("\r", (string) file_get_contents(base_path('docker/php/entrypoint.sh')));
    }
}
