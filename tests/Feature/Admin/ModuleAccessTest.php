<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\AdminPanelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class ModuleAccessTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AdminPanelSeeder::class);
    }

    public function test_view_alone_shows_the_list_and_no_action(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->userWith('visualizar_usuario'))
            ->get(route('admin.users.index', ['search' => $target->email]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('abilities.create', false)
                ->where('abilities.export', false)
                ->where('abilities.bulk', [])
                ->where('rows.data.0.can.update', false)
                ->where('rows.data.0.can.delete', false)
                ->where('rows.data.0.can.permissions', false)
            );
    }

    public function test_another_permission_without_view_has_no_effect_on_the_screen(): void
    {
        $this->actingAs($this->userWith('editar_usuario'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('auth.permissions', []));
    }

    /**
     * @return array<string, array{array<int, string>, string, string}>
     */
    public static function blockedActions(): array
    {
        return [
            'listar' => [['editar_usuario'], 'get', '/admin/users'],
            'abrir para editar' => [['editar_usuario'], 'get', '/admin/users/{target}/edit'],
            'alterar' => [['editar_usuario'], 'patch', '/admin/users/{target}'],
            'excluir' => [['excluir_usuario'], 'delete', '/admin/users/{target}'],
            'exportar usuários' => [['exportar_usuario'], 'get', '/admin/users/export'],
            'exportar perfis' => [['exportar_perfil'], 'get', '/admin/profiles/export'],
            'cadastrar perfil' => [['cadastrar_perfil'], 'get', '/admin/profiles/create'],
            'cadastrar permissão' => [['cadastrar_permissao'], 'get', '/admin/permissions/create'],
            'gerenciar sem o módulo' => [['gerenciar_permissoes_usuario', 'visualizar_usuario'], 'get', '/admin/users/{target}/permissions'],
            'salvar ambiente' => [['configura_env'], 'patch', '/admin/environment'],
        ];
    }

    /** @param array<int, string> $codes */
    #[DataProvider('blockedActions')]
    public function test_the_server_refuses_without_view(array $codes, string $method, string $url): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->userWith(...$codes))
            ->{$method}(str_replace('{target}', (string) $target->id, $url))
            ->assertForbidden();
    }

    public function test_export_follows_the_module(): void
    {
        $actor = $this->userWith('visualizar_usuario', 'exportar_usuario', 'visualizar_perfil');

        $this->actingAs($actor)->get('/admin/users/export?format=csv')->assertOk();
        $this->actingAs($actor)->get('/admin/profiles/export?format=csv')->assertForbidden();
    }

    public function test_the_dashboard_needs_no_permission(): void
    {
        $this->actingAs($this->userWith())->get(route('dashboard'))->assertOk();
    }

    public function test_the_dashboard_group_has_no_view_to_require(): void
    {
        $actor = $this->userWith('visualizar_historico');

        $this->assertContains('visualizar_historico', $actor->effectivePermissions());
    }

    public function test_an_administrator_needs_no_links(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)->get('/admin/users')->assertOk();
        $this->actingAs($administrator)->get('/admin/environment')->assertOk();
    }

    public function test_the_stored_permission_stays_when_it_has_no_effect(): void
    {
        $actor = $this->userWith('editar_usuario');

        $this->assertSame(['editar_usuario'], $actor->assignedPermissions());
        $this->assertSame([], $actor->effectivePermissions());
    }

    public function test_viewing_the_environment_does_not_allow_saving(): void
    {
        config(['template.install.editor_enabled' => true]);

        $actor = $this->userWith('visualizar_ambiente');

        $this->actingAs($actor)
            ->get('/admin/environment')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('editable', false));

        $this->actingAs($actor)->patch('/admin/environment', [])->assertForbidden();
    }

    public function test_who_can_edit_the_environment_sees_it_as_editable(): void
    {
        config(['template.install.editor_enabled' => true]);

        $this->actingAs($this->userWith('visualizar_ambiente', 'configura_env'))
            ->get('/admin/environment')
            ->assertInertia(fn (Assert $page) => $page->where('editable', true));
    }

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }
}
