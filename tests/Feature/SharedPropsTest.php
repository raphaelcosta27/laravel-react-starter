<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class SharedPropsTest extends TestCase
{
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
