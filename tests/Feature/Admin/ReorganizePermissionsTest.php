<?php

namespace Tests\Feature\Admin;

use App\Actions\Admin\ReorganizePermissions;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\ProfilePermission;
use App\Models\User;
use App\Models\UserPermission;
use Database\Seeders\AdminPanelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class ReorganizePermissionsTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    /** Como estava uma instalação antes das permissões por módulo. */
    private function legacy(string ...$codes): void
    {
        foreach ($codes as $code) {
            $permission = new Permission(['code' => $code, 'title' => $code, 'description' => $code]);
            $permission->is_system = true;
            $permission->save();
        }
    }

    private function reorganize(): void
    {
        app(ReorganizePermissions::class)->handle();
    }

    private function adjust(User $user, string $code, string $effect): void
    {
        UserPermission::create([
            'user_id' => $user->id,
            'permission_id' => Permission::firstWhere('code', $code)->id,
            'effect' => $effect,
        ]);
    }

    /** @return array<int, string> */
    private function assigned(User $user): array
    {
        return User::find($user->id)->assignedPermissions();
    }

    public function test_the_global_export_becomes_one_per_module(): void
    {
        $this->legacy('exportar_dados');
        $user = $this->userWith('exportar_dados');

        $this->reorganize();

        $this->assertEqualsCanonicalizing(
            ['exportar_perfil', 'exportar_permissao', 'exportar_usuario'],
            $this->assigned($user),
        );
    }

    public function test_the_global_export_is_kept_as_a_deleted_record_with_its_links(): void
    {
        $this->legacy('exportar_dados');
        $this->userWith('exportar_dados');

        $this->reorganize();

        $global = Permission::onlyTrashed()->firstWhere('code', 'exportar_dados');

        $this->assertNotNull($global);
        $this->assertSame(1, ProfilePermission::where('permission_id', $global->id)->count());
    }

    public function test_an_individual_grant_and_revocation_follow_the_export(): void
    {
        $this->legacy('exportar_dados');
        $granted = User::factory()->create();
        $revoked = $this->userWith('exportar_dados');
        $this->adjust($granted, 'exportar_dados', UserPermission::GRANT);
        $this->adjust($revoked, 'exportar_dados', UserPermission::REVOKE);

        $this->reorganize();

        $this->assertContains('exportar_usuario', $this->assigned($granted));
        $this->assertSame([], $this->assigned($revoked));
    }

    public function test_who_configures_the_environment_can_still_open_it(): void
    {
        $this->legacy('configura_env');
        $user = $this->userWith('configura_env');
        $other = $this->userWith('editar_usuario');

        $this->reorganize();

        $this->assertEqualsCanonicalizing(['configura_env', 'visualizar_ambiente'], $this->assigned($user));
        $this->assertNotContains('visualizar_ambiente', $this->assigned($other));
    }

    public function test_who_manages_user_permissions_can_still_do_it(): void
    {
        $this->legacy('gerenciar_permissoes_usuario', 'visualizar_permissao');
        $user = $this->userWith('gerenciar_permissoes_usuario');

        $this->reorganize();

        $this->assertContains('gerenciar_permissoes_usuario', User::find($user->id)->effectivePermissions());
        $this->assertContains('visualizar_permissao', User::find($user->id)->effectivePermissions());
    }

    public function test_a_revoked_manager_does_not_gain_the_list_of_permissions(): void
    {
        $this->legacy('gerenciar_permissoes_usuario', 'visualizar_permissao');
        $user = $this->userWith('gerenciar_permissoes_usuario');
        $this->adjust($user, 'gerenciar_permissoes_usuario', UserPermission::REVOKE);

        $this->reorganize();

        $this->assertNotContains('visualizar_permissao', $this->assigned($user));
    }

    public function test_a_revocation_does_not_remove_what_the_profile_already_gave(): void
    {
        $this->legacy('gerenciar_permissoes_usuario', 'visualizar_permissao');
        $user = $this->userWith('gerenciar_permissoes_usuario', 'visualizar_permissao');
        $this->adjust($user, 'gerenciar_permissoes_usuario', UserPermission::REVOKE);

        $this->reorganize();

        $this->assertContains('visualizar_permissao', $this->assigned($user));
    }

    public function test_every_catalog_permission_gets_its_module(): void
    {
        $this->reorganize();

        $this->assertSame(0, Permission::whereNull('module')->count());
        $this->assertSame('permissions', Permission::firstWhere('code', 'gerenciar_permissoes_usuario')->module);
    }

    public function test_titles_change_only_when_they_were_not_edited(): void
    {
        $this->legacy('cadastrar_perfil', 'cadastrar_usuario');
        Permission::firstWhere('code', 'cadastrar_perfil')->update(['title' => 'Cadastrar Perfis']);
        Permission::firstWhere('code', 'cadastrar_usuario')->update(['title' => 'Título próprio']);

        $this->reorganize();

        $this->assertSame('Criar Perfis', Permission::firstWhere('code', 'cadastrar_perfil')->title);
        $this->assertSame('Título próprio', Permission::firstWhere('code', 'cadastrar_usuario')->title);
    }

    public function test_a_second_run_changes_nothing(): void
    {
        $this->legacy('exportar_dados', 'configura_env', 'gerenciar_permissoes_usuario');
        $user = $this->userWith('exportar_dados', 'configura_env', 'gerenciar_permissoes_usuario');

        $this->reorganize();

        $this->adjust($user, 'exportar_usuario', UserPermission::REVOKE);
        ProfilePermission::where('permission_id', Permission::firstWhere('code', 'visualizar_ambiente')->id)->first()->delete();

        $links = ProfilePermission::count();
        $adjustments = UserPermission::count();
        $permissions = Permission::withTrashed()->count();

        $this->reorganize();
        $this->seed(AdminPanelSeeder::class);

        $this->assertSame($links, ProfilePermission::count());
        $this->assertSame($adjustments, UserPermission::count());
        $this->assertSame($permissions, Permission::withTrashed()->count());
        $this->assertNotContains('exportar_usuario', $this->assigned($user));
    }

    public function test_the_administrator_profile_is_not_touched(): void
    {
        $this->legacy('exportar_dados');

        $this->reorganize();

        $this->assertSame(0, Profile::administrator()->profilePermissions()->count());
    }
}
