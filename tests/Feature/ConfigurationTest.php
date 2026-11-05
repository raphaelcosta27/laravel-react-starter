<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_application_name_and_logo_come_from_configuration(): void
    {
        config([
            'app.name' => 'Outro Projeto',
            'template.branding.logo' => '/outro-logo.svg',
        ]);

        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->where('appName', 'Outro Projeto')
            ->where('branding.logo', '/outro-logo.svg')
            ->etc());
    }

    public function test_every_template_option_is_documented_in_the_example_file(): void
    {
        preg_match_all("/env\('([A-Z_]+)'/", File::get(config_path('template.php')), $matches);

        $example = File::get(base_path('.env.example'));

        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $variable) {
            $this->assertStringContainsString("{$variable}=", $example);
        }
    }

    public function test_setup_script_is_not_limited_by_composer_timeout(): void
    {
        $composer = json_decode(File::get(base_path('composer.json')), true);

        $this->assertSame('Composer\Config::disableProcessTimeout', $composer['scripts']['setup'][0]);
    }
}
