<?php

namespace Tests\Feature;

use Tests\TestCase;

class MissingConfigurationTest extends TestCase
{
    public function test_missing_key_shows_setup_guidance(): void
    {
        config(['app.key' => '']);
        app()->forgetInstance('encrypter');

        $this->get('/')
            ->assertStatus(500)
            ->assertSee('.env.example')
            ->assertSee('composer setup')
            ->assertDontSee('MissingAppKeyException');
    }
}
