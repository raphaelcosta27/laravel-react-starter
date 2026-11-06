<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PostalCodeTest extends TestCase
{
    use RefreshDatabase;

    private const VIACEP = [
        'cep' => '01310-100',
        'logradouro' => 'Avenida Paulista',
        'bairro' => 'Bela Vista',
        'localidade' => 'São Paulo',
        'uf' => 'SP',
    ];

    private const BRASILAPI = [
        'cep' => '01310100',
        'street' => 'Avenida Paulista',
        'neighborhood' => 'Bela Vista',
        'city' => 'São Paulo',
        'state' => 'SP',
    ];

    private const FOUND = [
        'street' => 'Avenida Paulista',
        'neighborhood' => 'Bela Vista',
        'city' => 'São Paulo',
        'state' => 'SP',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_a_postal_code_is_found(): void
    {
        Http::fake(['viacep.com.br/*' => Http::response(self::VIACEP)]);

        $this->getJson('/postal-code/01310100')->assertOk()->assertExactJson(self::FOUND);
    }

    public function test_only_the_postal_code_is_sent(): void
    {
        Http::fake(['*' => Http::response(self::VIACEP)]);

        $this->getJson('/postal-code/01310100');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://viacep.com.br/ws/01310100/json/');
        Http::assertSentCount(1);
    }

    public function test_a_failure_falls_back_to_the_next_service(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response('', 500),
            'brasilapi.com.br/*' => Http::response(self::BRASILAPI),
        ]);

        $this->getJson('/postal-code/01310100')->assertOk()->assertExactJson(self::FOUND);
    }

    public function test_a_connection_failure_falls_back_to_the_next_service(): void
    {
        Http::fake([
            'viacep.com.br/*' => fn () => throw new ConnectionException('fora'),
            'brasilapi.com.br/*' => Http::response(self::BRASILAPI),
        ]);

        $this->getJson('/postal-code/01310100')->assertOk();
    }

    public function test_an_unknown_postal_code_answers_404_without_trying_the_next_service(): void
    {
        Http::fake(['viacep.com.br/*' => Http::response(['erro' => 'true'])]);

        $this->getJson('/postal-code/99999999')->assertNotFound();

        Http::assertSentCount(1);
    }

    public function test_every_service_down_answers_503(): void
    {
        Http::fake(['*' => Http::response('', 503)]);

        $this->getJson('/postal-code/01310100')->assertServiceUnavailable();
    }

    public function test_a_generic_postal_code_returns_what_exists(): void
    {
        Http::fake(['*' => Http::response([...self::VIACEP, 'logradouro' => '', 'bairro' => ''])]);

        $this->getJson('/postal-code/13560970')->assertOk()->assertJson(['street' => '', 'city' => 'São Paulo']);
    }

    public function test_the_service_address_is_configurable(): void
    {
        config(['template.address.postal_code_urls' => ['https://cep.example.test/{cep}']]);
        Http::fake(['*' => Http::response(self::VIACEP)]);

        $this->getJson('/postal-code/01310100');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://cep.example.test/01310100');
    }

    public function test_only_eight_digits_reach_the_route(): void
    {
        Http::fake();

        $this->getJson('/postal-code/abc')->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_visitors_cannot_search(): void
    {
        auth()->logout();

        $this->getJson('/postal-code/01310100')->assertUnauthorized();
    }

    public function test_the_search_is_limited(): void
    {
        Http::fake(['*' => Http::response(self::VIACEP)]);

        foreach (range(1, 30) as $ignored) {
            $this->getJson('/postal-code/01310100')->assertOk();
        }

        $this->getJson('/postal-code/01310100')->assertTooManyRequests();
    }
}
