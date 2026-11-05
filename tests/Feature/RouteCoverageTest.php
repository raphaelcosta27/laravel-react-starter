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

    public function test_temporary_password_validity_is_checked(): void
    {
        foreach (['verification.notice', 'verification.verify', 'verification.send', 'password.change', 'password.update'] as $name) {
            $this->assertContains('temporary.password', Route::getRoutes()->getByName($name)->gatherMiddleware(), $name);
        }

        $this->assertContains('temporary.password', app(Kernel::class)->getMiddlewareGroups()['account.ready']);
    }
}
