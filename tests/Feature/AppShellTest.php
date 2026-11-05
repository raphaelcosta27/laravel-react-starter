<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppShellTest extends TestCase
{
    public function test_html_uses_active_locale_name_and_favicon_from_configuration(): void
    {
        app()->setLocale('pt_BR');

        config([
            'app.name' => 'Minha Aplicação',
            'template.branding.favicon' => '/icone.png',
        ]);

        $this->get('/login')
            ->assertSee('lang="pt-BR"', false)
            ->assertSee('data-app-name="Minha Aplicação"', false)
            ->assertSee('rel="icon" href="/icone.png"', false)
            ->assertSee('data-network-error="', false);
    }
}
