<?php

namespace Tests\Feature\Admin;

use App\Models\ChangeHistory;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Database\Seeders\AdminPanelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminPanelSeederTest extends TestCase
{
    use RefreshDatabase;

    private const CODES = [
        'visualizar_perfil', 'cadastrar_perfil', 'editar_perfil', 'excluir_perfil',
        'visualizar_permissao', 'cadastrar_permissao', 'editar_permissao', 'excluir_permissao',
        'visualizar_usuario', 'cadastrar_usuario', 'editar_usuario', 'alterar_situacao_usuario',
        'excluir_usuario', 'gerenciar_permissoes_usuario', 'solicitar_troca_senha',
        'enviar_email_autenticacao', 'exportar_dados', 'restaurar_perfil', 'restaurar_permissao',
        'restaurar_usuario',
    ];

    public function test_administrator_is_the_first_profile(): void
    {
        $this->seed(AdminPanelSeeder::class);

        $first = Profile::orderBy('id')->first();

        $this->assertSame(Profile::ADMINISTRATOR, $first->system_key);
        $this->assertSame('Administrador', $first->name);
    }

    public function test_default_profile_is_created_without_permissions(): void
    {
        $this->seed(AdminPanelSeeder::class);

        $default = Profile::default();

        $this->assertSame('Usuário', $default->name);
        $this->assertSame(0, $default->profilePermissions()->count());
    }

    public function test_panel_permissions_are_created_as_system_permissions(): void
    {
        $this->seed(AdminPanelSeeder::class);

        $this->assertEqualsCanonicalizing(self::CODES, Permission::pluck('code')->all());
        $this->assertSame(20, Permission::where('is_system', true)->count());
        $this->assertSame('Cadastrar Usuários', Permission::firstWhere('code', 'cadastrar_usuario')->title);
    }

    public function test_repeating_neither_duplicates_nor_undoes_changes(): void
    {
        $this->seed(AdminPanelSeeder::class);

        Profile::administrator()->update(['name' => 'Master']);
        Permission::firstWhere('code', 'editar_perfil')->update(['title' => 'Título novo']);

        $this->seed(AdminPanelSeeder::class);

        $this->assertSame(2, Profile::count());
        $this->assertSame(20, Permission::count());
        $this->assertSame('Master', Profile::administrator()->name);
        $this->assertSame('Título novo', Permission::firstWhere('code', 'editar_perfil')->title);
    }

    public function test_a_missing_permission_is_created_and_a_deleted_one_stays_deleted(): void
    {
        $this->seed(AdminPanelSeeder::class);

        Permission::firstWhere('code', 'exportar_dados')->delete();
        DB::table('permissions')->where('code', 'editar_perfil')->delete();

        $this->seed(AdminPanelSeeder::class);

        $this->assertTrue(Permission::where('code', 'editar_perfil')->exists());
        $this->assertFalse(Permission::where('code', 'exportar_dados')->exists());
        $this->assertSame(1, Permission::onlyTrashed()->where('code', 'exportar_dados')->count());
    }

    public function test_repeating_three_times_changes_no_data(): void
    {
        $this->seed(AdminPanelSeeder::class);

        $snapshot = fn () => [Permission::get()->toArray(), Profile::get()->toArray(), ChangeHistory::count()];
        $before = $snapshot();

        foreach (range(1, 3) as $run) {
            $this->seed(AdminPanelSeeder::class);
        }

        $this->assertSame($before, $snapshot());
    }

    public function test_oldest_user_becomes_administrator_and_the_others_get_the_default_profile(): void
    {
        $newest = User::factory()->create(['created_at' => now()]);
        $oldest = User::factory()->create(['created_at' => now()->subYear()]);

        $this->seed(AdminPanelSeeder::class);

        $this->assertTrue($oldest->fresh()->isAdministrator());
        $this->assertSame(Profile::default()->id, $newest->fresh()->profile_id);
    }

    public function test_existing_administrator_is_kept(): void
    {
        $this->seed(AdminPanelSeeder::class);
        $administrator = User::factory()->administrator()->create(['created_at' => now()]);
        $older = User::factory()->create(['created_at' => now()->subYear()]);

        $this->seed(AdminPanelSeeder::class);

        $this->assertTrue($administrator->fresh()->isAdministrator());
        $this->assertFalse($older->fresh()->isAdministrator());
    }
}
