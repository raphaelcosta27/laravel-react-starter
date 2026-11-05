<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class SharedPropsTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    public function test_pages_receive_the_effective_permissions(): void
    {
        $this->actingAs($this->userWith('gerar_relatorio'))
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.isAdministrator', false)
                ->where('auth.permissions', ['gerar_relatorio'])
                ->etc());
    }

    public function test_administrators_receive_an_empty_list_and_the_flag(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.isAdministrator', true)
                ->where('auth.permissions', [])
                ->etc());
    }

    public function test_pages_receive_the_flash_message(): void
    {
        $flash = ['type' => 'success', 'message' => 'Salvo'];

        $this->withSession(['flash' => $flash])
            ->get('/login')
            ->assertInertia(fn (Assert $page) => $page->where('flash', $flash)->etc());
    }

    public function test_permissions_are_empty_when_they_cannot_be_read(): void
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => throw new RuntimeException('Banco indisponível'));

        $shared = (new HandleInertiaRequests)->share($request);

        $this->assertFalse($shared['auth']['isAdministrator']);
        $this->assertSame([], $shared['auth']['permissions']);
        $this->assertNull($shared['flash']);
    }

    public function test_pages_receive_application_name_and_branding(): void
    {
        config([
            'app.name' => 'Minha Aplicação',
            'template.branding.logo' => '/marca.png',
        ]);

        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->where('appName', 'Minha Aplicação')
            ->where('branding.logo', '/marca.png')
            ->where('auth.user', null)
            ->etc());
    }

    public function test_pages_receive_the_configured_colors(): void
    {
        config([
            'template.branding.primary_color' => '#00695c',
            'template.branding.secondary_color' => '#6A1B9A',
        ]);

        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->where('branding.primaryColor', '#00695c')
            ->where('branding.secondaryColor', '#6A1B9A')
            ->etc());
    }

    public function test_colors_outside_the_hexadecimal_format_are_not_shared(): void
    {
        config([
            'template.branding.primary_color' => 'azul',
            'template.branding.secondary_color' => '#12',
        ]);

        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->where('branding.primaryColor', null)
            ->where('branding.secondaryColor', null)
            ->etc());
    }

    public function test_colors_are_empty_by_default(): void
    {
        config([
            'template.branding.primary_color' => null,
            'template.branding.secondary_color' => null,
        ]);

        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->where('branding.primaryColor', null)
            ->etc());
    }

    public function test_pages_receive_translations_of_the_active_locale(): void
    {
        app()->setLocale('pt_BR');

        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->where('translations.Log in', 'Entrar')
            ->etc());
    }

    public function test_user_is_null_when_it_cannot_be_read(): void
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => throw new RuntimeException('Banco indisponível'));

        $shared = (new HandleInertiaRequests)->share($request);

        $this->assertNull($shared['auth']['user']);
    }
}
