<?php

namespace Tests\Feature\Admin;

use App\Policies\ProfilePolicy;
use App\Policies\UserPolicy;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermissionCoverageTest extends TestCase
{
    private const AUTHENTICATION = 'Fluxo de autenticação, anterior a qualquer permissão.';

    private const OWN_ACCOUNT = 'Age somente sobre a própria conta.';

    private const SIGNED_FILE = 'Arquivo por URL assinada pelo framework.';

    /** Funções sem permissão e o motivo de cada uma. */
    private const ROUTE_EXCEPTIONS = [
        'GET /' => 'Página pública.',
        'GET up' => 'Verificação de saúde, sem dados.',
        'GET storage/{path}' => self::SIGNED_FILE,
        'PUT storage/{path}' => self::SIGNED_FILE,
        'GET dashboard' => 'Página inicial de toda conta liberada.',
        'GET access/request' => self::AUTHENTICATION,
        'POST access/request' => self::AUTHENTICATION,
        'GET login' => self::AUTHENTICATION,
        'POST login' => self::AUTHENTICATION,
        'GET login/code' => self::AUTHENTICATION,
        'POST login/code' => self::AUTHENTICATION,
        'POST login/code/resend' => self::AUTHENTICATION,
        'POST logout' => self::AUTHENTICATION,
        'GET register' => self::AUTHENTICATION,
        'POST register' => self::AUTHENTICATION,
        'GET forgot-password' => self::AUTHENTICATION,
        'POST forgot-password' => self::AUTHENTICATION,
        'GET reset-password/{token}' => self::AUTHENTICATION,
        'POST reset-password' => self::AUTHENTICATION,
        'GET verify-email' => self::AUTHENTICATION,
        'GET verify-email/{id}/{hash}' => self::AUTHENTICATION,
        'POST email/verification-notification' => self::AUTHENTICATION,
        'GET profile/avatar' => self::OWN_ACCOUNT,
        'POST profile/avatar' => self::OWN_ACCOUNT,
        'DELETE profile/avatar' => self::OWN_ACCOUNT,
        'POST profile/contacts' => self::OWN_ACCOUNT,
        'PATCH profile/contacts/{contact}' => self::OWN_ACCOUNT,
        'DELETE profile/contacts/{contact}' => self::OWN_ACCOUNT,
        'GET notifications' => self::OWN_ACCOUNT,
        'GET notifications/unread' => self::OWN_ACCOUNT,
        'POST notifications/seen' => self::OWN_ACCOUNT,
        'DELETE notifications/{notification}' => self::OWN_ACCOUNT,
        'POST profile/addresses' => self::OWN_ACCOUNT,
        'PATCH profile/addresses/{address}' => self::OWN_ACCOUNT,
        'DELETE profile/addresses/{address}' => self::OWN_ACCOUNT,
        'GET postal-code/{postalCode}' => 'Consulta pública de CEP, para quem está logado e com limite de uso.',
        'GET confirm-password' => self::AUTHENTICATION,
        'POST confirm-password' => self::AUTHENTICATION,
        'GET password/change' => self::OWN_ACCOUNT,
        'PUT password' => self::OWN_ACCOUNT,
        'GET profile' => self::OWN_ACCOUNT,
        'PATCH profile' => self::OWN_ACCOUNT,
        'DELETE profile' => self::OWN_ACCOUNT,
    ];

    /** Comandos rodam no servidor, fora do controle de acesso do painel. */
    private const COMMAND_EXCEPTIONS = [
        'app:clean-logs' => 'Rotina agendada de limpeza de logs.',
        'app:create-user' => 'Usado por quem opera o servidor.',
        'app:setup' => 'Usado por quem opera o servidor.',
    ];

    private function key(RoutingRoute $route): string
    {
        return $route->methods()[0].' '.$route->uri();
    }

    /** @return array<int, string> */
    private function requiredCodes(RoutingRoute $route): array
    {
        return collect($route->gatherMiddleware())
            ->filter(fn ($item) => is_string($item) && str_starts_with($item, 'can:'))
            ->map(fn (string $item) => explode(',', substr($item, 4))[0])
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function uncoveredRoutes(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route) => $this->requiredCodes($route) === [])
            ->map(fn (RoutingRoute $route) => $this->key($route))
            ->reject(fn (string $key) => array_key_exists($key, self::ROUTE_EXCEPTIONS))
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function unknownCodes(string $source): array
    {
        preg_match_all('/(?:\bcan\(|Gate::\w+\(|permission:\s*)\'(\w+)\'|can:(\w+)/', $source, $matches);

        $abilities = [...get_class_methods(UserPolicy::class), ...get_class_methods(ProfilePolicy::class)];

        return collect([...$matches[1], ...$matches[2]])
            ->filter()
            ->unique()
            ->reject(fn (string $code) => in_array($code, $abilities, true))
            ->reject(fn (string $code) => array_key_exists($code, trans('permissions', [], 'pt_BR')))
            ->values()
            ->all();
    }

    public function test_every_route_requires_a_permission_or_is_a_listed_exception(): void
    {
        $this->assertSame([], $this->uncoveredRoutes());
    }

    public function test_a_new_route_without_a_permission_is_pointed_out(): void
    {
        Route::get('admin/nova-funcao', fn () => 'ok')->middleware('account.ready');

        $this->assertSame(['GET admin/nova-funcao'], $this->uncoveredRoutes());
    }

    public function test_exceptions_point_to_existing_unprotected_routes(): void
    {
        $unprotected = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route) => $this->requiredCodes($route) === [])
            ->map(fn (RoutingRoute $route) => $this->key($route))
            ->all();

        $this->assertSame([], array_values(array_diff(array_keys(self::ROUTE_EXCEPTIONS), $unprotected)));
    }

    public function test_every_application_command_is_a_listed_exception(): void
    {
        $commands = array_filter(array_keys(Artisan::all()), fn (string $name) => str_starts_with($name, 'app:'));

        $this->assertEqualsCanonicalizing(array_keys(self::COMMAND_EXCEPTIONS), array_values($commands));
    }

    public function test_routes_only_use_codes_from_the_seeder(): void
    {
        $codes = collect(Route::getRoutes()->getRoutes())->flatMap(fn (RoutingRoute $route) => $this->requiredCodes($route));

        $this->assertNotEmpty($codes);
        $this->assertSame([], array_values(array_diff($codes->unique()->all(), array_keys(trans('permissions', [], 'pt_BR')))));
    }

    public function test_the_source_code_only_uses_codes_from_the_seeder(): void
    {
        $source = collect([app_path(), base_path('routes'), resource_path('js')])
            ->flatMap(fn (string $directory) => File::allFiles($directory))
            ->reject(fn ($file) => str_contains($file->getPathname(), '__tests__'))
            ->map(fn ($file) => $file->getContents())
            ->implode("\n");

        $this->assertSame([], $this->unknownCodes($source));
    }

    public function test_an_unknown_code_is_pointed_out(): void
    {
        $source = "\$actor->can('update', \$row); \$actor->can('codigo_novo'); ->middleware('can:outro_codigo'); permission: 'mais_um'";

        $this->assertEqualsCanonicalizing(['codigo_novo', 'outro_codigo', 'mais_um'], $this->unknownCodes($source));
    }
}
