<?php

namespace Tests\Feature\Admin;

use App\Support\Permissions\Modules;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Todo módulo novo traz suas permissões. Veja
 * docs/especificacoes/permissoes-por-modulo.md.
 */
class ModuleRulesTest extends TestCase
{
    private const NO_VIEW = ['dashboard'];

    /**
     * @param  array<string, array<string, mixed>>  $modules
     * @param  array<int, string>  $permissions
     * @param  array<int, string>  $menu
     * @return array<int, string>
     */
    private function problems(array $modules, array $permissions, array $menu): array
    {
        $problems = [];
        $declared = [];

        foreach ($modules as $key => $module) {
            $basic = $module['basic'] ?? [];
            $declared = [...$declared, ...$basic, ...($module['specific'] ?? [])];

            if (in_array($key, self::NO_VIEW, true)) {
                continue;
            }

            if (($module['view'] ?? null) === null || ! in_array($module['view'], $basic, true)) {
                $problems[] = "módulo {$key} sem Visualizar";
            }

            if (! ($module['without_records'] ?? false) && count($basic) !== 4) {
                $problems[] = "módulo {$key} sem as quatro permissões básicas";
            }
        }

        foreach (array_diff($permissions, $declared) as $code) {
            $problems[] = "permissão {$code} sem módulo";
        }

        foreach (array_diff($declared, $permissions) as $code) {
            $problems[] = "módulo com a permissão {$code}, que não está em lang/pt_BR/permissions.php";
        }

        $views = array_filter(array_column($modules, 'view'));

        foreach (array_diff($menu, $views) as $code) {
            $problems[] = "menu {$code} sem módulo";
        }

        return $problems;
    }

    /** @return array<int, string> */
    private function menuPermissions(): array
    {
        preg_match_all("/permission: '([a-z_]+)'/", File::get(resource_path('js/Layouts/AuthenticatedLayout.jsx')), $matches);

        return $matches[1];
    }

    public function test_the_system_follows_the_module_rules(): void
    {
        $this->assertNotEmpty($this->menuPermissions());

        $this->assertSame([], $this->problems(
            Modules::all(),
            array_keys(trans('permissions', [], 'pt_BR')),
            $this->menuPermissions(),
        ));
    }

    public function test_a_module_without_view_is_pointed_out(): void
    {
        $modules = ['novo' => ['view' => null, 'basic' => ['criar_novo', 'editar_novo', 'excluir_novo', 'outra_nova']]];

        $this->assertContains('módulo novo sem Visualizar', $this->problems($modules, ['criar_novo', 'editar_novo', 'excluir_novo', 'outra_nova'], []));
    }

    public function test_a_module_without_the_basic_permissions_is_pointed_out(): void
    {
        $modules = ['novo' => ['view' => 'ver_novo', 'basic' => ['ver_novo']]];

        $this->assertContains('módulo novo sem as quatro permissões básicas', $this->problems($modules, ['ver_novo'], []));
    }

    public function test_a_permission_without_module_is_pointed_out(): void
    {
        $this->assertContains('permissão solta sem módulo', $this->problems([], ['solta'], []));
    }

    public function test_a_menu_without_module_is_pointed_out(): void
    {
        $this->assertContains('menu ver_solto sem módulo', $this->problems([], [], ['ver_solto']));
    }
}
