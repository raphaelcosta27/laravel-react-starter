<?php

namespace Tests\Feature\Admin;

use App\Http\Requests\Admin\StorePermissionRequest;
use App\Models\Permission;
use App\Support\Permissions\Modules;
use App\Support\Permissions\PackageModules;
use Database\Seeders\AdminPanelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminData;
use Tests\Support\FakePackageModule;
use Tests\TestCase;

/**
 * Módulos declarados por pacotes Composer. Veja
 * docs/especificacoes/modulos-de-pacotes.md.
 */
class PackageModulesTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private const FAKE = FakePackageModule::KEY;

    protected function tearDown(): void
    {
        PackageModules::flush();

        parent::tearDown();
    }

    // Registro

    public function test_a_valid_module_is_registered_without_its_texts_and_menu(): void
    {
        FakePackageModule::register();

        $module = PackageModules::all()[self::FAKE];

        $this->assertSame('visualizar_fake', $module['view']);
        $this->assertSame(['publicar_fake'], $module['specific']);
        $this->assertArrayNotHasKey('permissions', $module);
        $this->assertArrayNotHasKey('menu', $module);
        $this->assertSame('Visualizar Fake', PackageModules::permissions()['visualizar_fake']['title']);
    }

    public function test_flush_empties_the_registry(): void
    {
        FakePackageModule::register();

        PackageModules::flush();

        $this->assertSame([], PackageModules::all());
        $this->assertSame([], PackageModules::permissions());
        $this->assertSame([], PackageModules::menus());
    }

    public function test_each_application_starts_with_an_empty_registry(): void
    {
        FakePackageModule::register();

        $this->refreshApplication();

        $this->assertSame([], PackageModules::all());
    }

    public function test_nothing_is_written_to_the_configuration(): void
    {
        FakePackageModule::register();

        $this->assertArrayNotHasKey(self::FAKE, config('template.modules'));
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function invalidDefinitions(): array
    {
        $definition = FakePackageModule::definition();
        $menu = $definition['menu'];

        return [
            'sem rótulo' => [['label' => null], 'label'],
            'sem básicas' => [['basic' => []], 'basic'],
            'Visualizar fora das básicas' => [['view' => 'publicar_fake'], 'view'],
            'código sem texto' => [['specific' => ['publicar_fake', 'arquivar_fake']], 'arquivar_fake'],
            'texto sem título' => [['permissions' => [...$definition['permissions'], 'publicar_fake' => ['description' => 'x']]], 'publicar_fake'],
            'texto sem descrição' => [['permissions' => [...$definition['permissions'], 'publicar_fake' => ['title' => 'x']]], 'publicar_fake'],
            'código do Template' => [['specific' => ['visualizar_usuario'], 'permissions' => [...$definition['permissions'], 'visualizar_usuario' => ['title' => 'x', 'description' => 'y']]], 'visualizar_usuario'],
            'menu sem rótulo' => [['menu' => [...$menu, 'label' => null]], 'menu'],
            'menu sem rota' => [['menu' => [...$menu, 'route' => '']], 'menu'],
            'menu sem entrada' => [['menu' => array_diff_key($menu, ['index' => true])], 'menu'],
            'menu sem ícone' => [['menu' => [...$menu, 'icon' => null]], 'menu'],
            'grupo desconhecido' => [['menu' => [...$menu, 'group' => 'outro']], 'group'],
        ];
    }

    /** @param  array<string, mixed>  $overrides */
    #[DataProvider('invalidDefinitions')]
    public function test_an_invalid_definition_is_refused(array $overrides, string $mention): void
    {
        try {
            FakePackageModule::register($overrides);
            $this->fail('O registro deveria falhar.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Módulo de pacote ['.self::FAKE.']', $exception->getMessage());
            $this->assertStringContainsString($mention, $exception->getMessage());
        }

        $this->assertSame([], PackageModules::all());
    }

    public function test_a_key_from_the_template_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Módulo de pacote [users]');

        PackageModules::register('users', FakePackageModule::definition());
    }

    public function test_a_key_registered_twice_is_refused(): void
    {
        FakePackageModule::register();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Módulo de pacote ['.self::FAKE.']');

        FakePackageModule::register();
    }

    public function test_a_code_from_another_package_is_refused(): void
    {
        FakePackageModule::register();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('visualizar_fake');

        PackageModules::register('other_module', FakePackageModule::definition(['menu' => null]));
    }

    public function test_menu_and_group_are_optional(): void
    {
        FakePackageModule::register(['menu' => null]);
        PackageModules::register('second_module', [
            ...FakePackageModule::definition(),
            'basic' => ['ver_segundo'],
            'view' => 'ver_segundo',
            'specific' => [],
            'permissions' => ['ver_segundo' => ['title' => 'Ver segundo', 'description' => 'Permite ver.']],
            'menu' => ['label' => 'Second', 'route' => 'admin.second', 'index' => 'admin.second.index', 'icon' => 'tune'],
        ]);

        $this->assertSame([[
            'key' => 'second_module',
            'label' => 'Second',
            'route' => 'admin.second',
            'index' => 'admin.second.index',
            'icon' => 'tune',
            'group' => null,
            'permission' => 'ver_segundo',
        ]], PackageModules::menus());
    }

    // Modules

    public function test_modules_include_the_package_after_the_template(): void
    {
        FakePackageModule::register();

        $template = array_keys(config('template.modules'));

        $this->assertSame([...$template, self::FAKE], Modules::keys());
        $this->assertSame('visualizar_fake', Modules::view(self::FAKE));
        $this->assertSame(
            ['visualizar_fake', 'cadastrar_fake', 'editar_fake', 'excluir_fake', 'publicar_fake'],
            Modules::codes(self::FAKE),
        );
        $this->assertSame(self::FAKE, Modules::catalog()['publicar_fake']);
        $this->assertSame(['value' => self::FAKE, 'label' => 'Fake module'], collect(Modules::options())->last());
        $this->assertSame('Fake module', Modules::label(self::FAKE));
    }

    public function test_without_view_the_package_permissions_have_no_effect(): void
    {
        FakePackageModule::register();
        $this->seed(AdminPanelSeeder::class);

        $this->assertSame([], Modules::effective(['editar_fake']));
        $this->assertSame(['visualizar_fake', 'editar_fake'], Modules::effective(['visualizar_fake', 'editar_fake']));

        $this->assertNotContains('editar_fake', $this->userWith('editar_fake')->effectivePermissions());
        $this->assertContains('editar_fake', $this->userWith('visualizar_fake', 'editar_fake')->effectivePermissions());
    }

    public function test_groups_show_the_package_module_with_the_off_notice(): void
    {
        config(['template.fake_enabled' => false]);
        FakePackageModule::register(['enabled_by' => 'template.fake_enabled']);
        $this->seed(AdminPanelSeeder::class);

        $group = collect(Modules::groups(Permission::all(), fn (Permission $permission) => $permission->code))
            ->firstWhere('key', self::FAKE);

        $this->assertSame('Fake module', $group['label']);
        $this->assertSame(Permission::firstWhere('code', 'visualizar_fake')->id, $group['view']);
        $this->assertSame(['visualizar_fake', 'cadastrar_fake', 'editar_fake', 'excluir_fake', 'publicar_fake'], $group['permissions']);
        $this->assertNotNull($group['notice']);
    }

    public function test_a_permission_can_be_created_in_the_package_module(): void
    {
        FakePackageModule::register();

        $rules = (new StorePermissionRequest)->rules();

        $this->assertTrue(Validator::make(['module' => self::FAKE], ['module' => $rules['module']])->passes());
    }

    // Preparação do painel

    public function test_the_seeder_creates_the_package_permissions(): void
    {
        FakePackageModule::register();

        $this->seed(AdminPanelSeeder::class);

        $permission = Permission::firstWhere('code', 'publicar_fake');

        $this->assertSame(self::FAKE, $permission->module);
        $this->assertSame('Publicar Fake', $permission->title);
        $this->assertSame('Permite publicar no módulo falso.', $permission->description);
        $this->assertTrue($permission->is_system);
    }

    public function test_the_seeder_can_run_again_without_changes(): void
    {
        FakePackageModule::register();
        $this->seed(AdminPanelSeeder::class);

        Permission::firstWhere('code', 'editar_fake')->update(['title' => 'Título da pessoa']);
        Permission::firstWhere('code', 'excluir_fake')->delete();
        $before = $this->snapshot();

        $this->seed(AdminPanelSeeder::class);

        $this->assertSame($before, $this->snapshot());
        $this->assertSame('Título da pessoa', Permission::firstWhere('code', 'editar_fake')->title);
        $this->assertSame(1, Permission::withTrashed()->where('code', 'excluir_fake')->count());
    }

    public function test_an_existing_permission_with_the_same_code_only_receives_the_module(): void
    {
        $existing = Permission::factory()->create(['code' => 'publicar_fake', 'title' => 'Criada pela tela', 'module' => null]);
        FakePackageModule::register();

        $this->seed(AdminPanelSeeder::class);

        $this->assertSame(1, Permission::withTrashed()->where('code', 'publicar_fake')->count());
        $this->assertSame('Criada pela tela', $existing->fresh()->title);
        $this->assertSame(self::FAKE, $existing->fresh()->module);
    }

    public function test_without_packages_the_seeder_creates_no_extra_permission(): void
    {
        $this->seed(AdminPanelSeeder::class);

        $this->assertSame([], Permission::where('code', 'like', '%_fake')->pluck('code')->all());
    }

    /** @return array<int, array<string, mixed>> */
    private function snapshot(): array
    {
        return Permission::withTrashed()->orderBy('id')->get()
            ->map(fn (Permission $permission) => [...$permission->only('code', 'title', 'module'), 'deleted' => $permission->trashed()])
            ->all();
    }

    // Telas

    public function test_the_permissions_screen_offers_the_package_module(): void
    {
        FakePackageModule::register();
        $this->seed(AdminPanelSeeder::class);

        $this->actingAs($this->userWith('visualizar_permissao'))
            ->get(route('admin.permissions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('modules', fn ($modules) => collect($modules)->last() === ['value' => self::FAKE, 'label' => 'Fake module']));
    }

    public function test_the_user_permissions_dialog_groups_the_package_module(): void
    {
        FakePackageModule::register();
        $this->seed(AdminPanelSeeder::class);
        $target = $this->userWith('visualizar_fake');

        $this->actingAs($this->userWith('visualizar_permissao', 'gerenciar_permissoes_usuario'))
            ->get(route('admin.permissions.users.edit', $target))
            ->assertInertia(fn (Assert $page) => $page
                ->where('form.modules', function ($modules) {
                    $group = collect($modules)->firstWhere('key', self::FAKE);

                    return $group !== null
                        && $group['label'] === 'Fake module'
                        && collect($group['permissions'])->pluck('code')->all() === Modules::codes(self::FAKE);
                }));
    }

    public function test_the_profile_screen_groups_the_package_module(): void
    {
        FakePackageModule::register();
        $this->seed(AdminPanelSeeder::class);

        $this->actingAs($this->userWith('visualizar_perfil', 'cadastrar_perfil'))
            ->get(route('admin.profiles.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('form.modules', fn ($modules) => collect($modules)->pluck('key')->contains(self::FAKE)));
    }

    // Menu

    public function test_shared_props_send_the_package_menu(): void
    {
        FakePackageModule::register();

        $this->actingAs($this->userWith())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('packageNavigation', [[
                'key' => self::FAKE,
                'label' => 'Fake module',
                'route' => 'admin.fake',
                'index' => 'admin.fake.index',
                'icon' => 'article',
                'group' => 'config',
                'permission' => 'visualizar_fake',
            ]]));
    }

    public function test_without_packages_the_menu_prop_is_empty(): void
    {
        $this->actingAs($this->userWith())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('packageNavigation', []));
    }

    // Páginas

    public function test_a_package_page_is_protected_by_its_permission(): void
    {
        FakePackageModule::register();
        $this->seed(AdminPanelSeeder::class);

        Route::middleware(['web', 'auth', 'account.ready', 'can:visualizar_fake'])
            ->get('admin/fake', fn () => Inertia::render('fake::Admin/Index'))
            ->name('admin.fake.index');

        $this->actingAs($this->userWith('editar_fake'))->get('/admin/fake')->assertForbidden();

        $this->actingAs($this->userWith('visualizar_fake'))
            ->get('/admin/fake')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('fake::Admin/Index', false));
    }
}
