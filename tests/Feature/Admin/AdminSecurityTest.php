<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    /** @return array<int, LaravelRoute> */
    private function panelRoutes(): array
    {
        return collect(Route::getRoutes())
            ->filter(fn (LaravelRoute $route) => str_starts_with((string) $route->getName(), 'admin.'))
            ->values()
            ->all();
    }

    public function test_every_panel_route_refuses_a_user_without_permissions(): void
    {
        $target = User::factory()->withProfile(Profile::factory()->create())->create();
        $profile = Profile::factory()->create();
        $permission = Permission::factory()->create();

        $parameters = [
            '{user}' => $target->id,
            '{profile}' => $profile->id,
            '{permission}' => $permission->id,
        ];

        $routes = $this->panelRoutes();

        $this->assertCount(33, $routes);

        foreach ($routes as $route) {
            $method = collect($route->methods())->reject(fn (string $verb) => $verb === 'HEAD')->first();
            $uri = strtr($route->uri(), $parameters);

            $this->actingAs($this->userWith())
                ->call($method, '/'.$uri)
                ->assertForbidden();
        }
    }

    public function test_guest_never_reaches_a_panel_route(): void
    {
        foreach ($this->panelRoutes() as $route) {
            $method = collect($route->methods())->reject(fn (string $verb) => $verb === 'HEAD')->first();
            $uri = preg_replace('/\{\w+\}/', '1', $route->uri());

            $this->call($method, '/'.$uri)->assertRedirect(route('login'));
        }
    }

    public function test_non_administrator_with_every_permission_cannot_touch_an_administrator(): void
    {
        $codes = [
            'cadastrar_usuario', 'editar_usuario', 'alterar_situacao_usuario', 'excluir_usuario',
            'gerenciar_permissoes_usuario', 'solicitar_troca_senha', 'enviar_email_autenticacao',
            'restaurar_usuario', 'visualizar_usuario', 'editar_perfil', 'excluir_perfil',
        ];

        $actor = $this->userWith(...$codes);
        $administrator = User::factory()->administrator()->unverified()->create();
        $master = Profile::administrator();
        $url = "/admin/users/{$administrator->id}";

        $this->actingAs($actor)->post('/admin/users', ['name' => 'A', 'email' => 'a@example.com', 'profile_id' => $master->id])->assertForbidden();
        $this->actingAs($actor)->patch($url, ['name' => 'Tomado'])->assertForbidden();
        $this->actingAs($actor)->patch($url.'/status', ['is_active' => false])->assertForbidden();
        $this->actingAs($actor)->delete($url)->assertForbidden();
        $this->actingAs($actor)->put($url.'/permissions', ['adjustments' => []])->assertForbidden();
        $this->actingAs($actor)->post($url.'/password-change')->assertForbidden();
        $this->actingAs($actor)->post($url.'/authentication-email')->assertForbidden();
        $this->actingAs($actor)->patch("/admin/users/{$actor->id}", ['profile_id' => $master->id])->assertForbidden();
        $this->actingAs($actor)->patch("/admin/profiles/{$master->id}", ['name' => 'Tomado'])->assertForbidden();

        $administrator->refresh();

        $this->assertTrue($administrator->is_active);
        $this->assertNotSame('Tomado', $administrator->name);
        $this->assertFalse(User::find($actor->id)->isAdministrator());
        $this->assertSame(1, User::where('profile_id', $master->id)->count());
    }
}
