<?php

namespace Tests\Feature;

use Tests\TestCase;

class ManifestTest extends TestCase
{
    public function test_entrega_nome_cores_e_icones_da_configuracao_sem_sessao(): void
    {
        config([
            'app.name' => 'Minha Empresa',
            'template.branding.primary_color' => '#00695c',
            'template.branding.icon' => '/marca/app',
        ]);

        $this->get(route('manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJson([
                'name' => 'Minha Empresa',
                'display' => 'standalone',
                'theme_color' => '#00695c',
                'icons' => [
                    ['src' => '/marca/app-192.png', 'sizes' => '192x192'],
                    ['src' => '/marca/app-512.png', 'sizes' => '512x512'],
                ],
            ]);
    }

    public function test_cor_invalida_usa_o_padrao(): void
    {
        config(['template.branding.primary_color' => 'azul']);

        $this->get(route('manifest'))->assertJson(['theme_color' => '#3949ab']);
    }

    public function test_os_icones_padrao_existem(): void
    {
        $this->assertFileExists(public_path('images/icon-192.png'));
        $this->assertFileExists(public_path('images/icon-512.png'));
    }

    public function test_a_pagina_aponta_para_o_manifesto(): void
    {
        $this->get(route('login'))
            ->assertSee('rel="manifest"', false)
            ->assertSee('viewport-fit=cover', false);
    }
}
