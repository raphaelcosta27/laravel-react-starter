<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);
    }

    #[DataProvider('statuses')]
    public function test_errors_render_the_error_page(int $status): void
    {
        Route::middleware('web')->get('/_erro', fn () => abort($status));

        $this->get('/_erro')
            ->assertStatus($status)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Error')
                ->where('status', $status)
                ->etc());
    }

    public static function statuses(): array
    {
        return [[403], [404], [419], [429], [500], [503]];
    }

    public function test_unknown_route_renders_error_page_with_shared_data(): void
    {
        config(['app.name' => 'Minha Aplicação']);

        $this->get('/rota-inexistente')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Error')
                ->where('appName', 'Minha Aplicação')
                ->has('translations')
                ->etc());
    }

    public function test_unexpected_exception_hides_technical_details(): void
    {
        Route::middleware('web')->get('/_falha', fn () => throw new RuntimeException('detalhe interno'));

        $this->get('/_falha')
            ->assertStatus(500)
            ->assertDontSee('detalhe interno')
            ->assertInertia(fn (Assert $page) => $page->component('Error')->etc());
    }
}
