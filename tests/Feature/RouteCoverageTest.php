<?php

namespace Tests\Feature;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteCoverageTest extends TestCase
{
    /** Rotas autenticadas que não exigem conta liberada. */
    private const EXCEPTIONS = [
        'verification.notice',
        'verification.verify',
        'verification.send',
        'logout',
        'password.change',
        'password.update',
    ];

    public function test_authenticated_routes_require_a_released_account(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (! in_array('auth', $route->gatherMiddleware(), true)) {
                continue;
            }

            $this->assertContains(
                $route->getName(),
                self::EXCEPTIONS,
                "A rota [{$route->uri()}] deve usar o grupo account.ready.",
            );
        }
    }

    public function test_area_routes_use_the_released_account_group(): void
    {
        foreach (['dashboard', 'profile.edit', 'profile.update', 'profile.destroy', 'password.confirm'] as $name) {
            $this->assertContains('account.ready', Route::getRoutes()->getByName($name)->gatherMiddleware(), $name);
        }
    }

    public function test_panel_routes_require_a_released_account_and_a_permission(): void
    {
        $routes = collect(Route::getRoutes())->filter(
            fn ($route) => str_starts_with((string) $route->getName(), 'admin.'),
        );

        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();

            $this->assertContains('account.ready', $middleware, $route->getName());
            $this->assertNotEmpty(
                array_filter($middleware, fn ($item) => is_string($item) && str_starts_with($item, 'can:')),
                "A rota [{$route->getName()}] deve exigir uma permissão.",
            );
        }
    }

    public function test_inactive_accounts_are_checked(): void
    {
        foreach (['verification.notice', 'verification.verify', 'verification.send', 'password.change', 'password.update'] as $name) {
            $this->assertContains('account.active', Route::getRoutes()->getByName($name)->gatherMiddleware(), $name);
        }

        $this->assertContains('account.active', app(Kernel::class)->getMiddlewareGroups()['account.ready']);
    }

    public function test_temporary_password_validity_is_checked(): void
    {
        foreach (['verification.notice', 'verification.verify', 'verification.send', 'password.change', 'password.update'] as $name) {
            $this->assertContains('temporary.password', Route::getRoutes()->getByName($name)->gatherMiddleware(), $name);
        }

        $this->assertContains('temporary.password', app(Kernel::class)->getMiddlewareGroups()['account.ready']);
    }
}
