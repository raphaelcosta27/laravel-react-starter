<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    private function withBrokenDatabase(callable $callback): void
    {
        $default = config('database.default');

        config([
            'database.default' => 'broken',
            'database.connections.broken' => [
                'driver' => 'sqlite',
                'database' => storage_path('framework/testing/inexistente.sqlite'),
            ],
        ]);

        DB::purge('broken');

        try {
            $callback();
        } finally {
            config(['database.default' => $default]);
        }
    }

    public function test_home_shows_backend_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('canLogin', true)
                ->where('canRegister', false)
                ->where('status.database', true)
                ->where('status.checkedAt', fn (string $date) => strtotime($date) !== false && str_contains($date, 'T')));
    }

    public function test_home_opens_for_guests_when_database_is_down(): void
    {
        $this->withBrokenDatabase(fn () => $this->get('/')
            ->assertOk()
            ->assertDontSee('inexistente.sqlite')
            ->assertInertia(fn (Assert $page) => $page->where('status.database', false)->etc()));
    }

    public function test_home_opens_for_logged_users_when_database_is_down(): void
    {
        $user = User::factory()->create();
        $sessionKey = Auth::guard('web')->getName();

        $this->withBrokenDatabase(fn () => $this->withSession([$sessionKey => $user->getKey()])
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('status.database', false)
                ->where('auth.user', null)
                ->etc()));
    }
}
