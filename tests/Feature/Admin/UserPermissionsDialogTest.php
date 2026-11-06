<?php

namespace Tests\Feature\Admin;

use App\Models\ChangeHistory;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserPermission;
use Database\Seeders\AdminPanelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class UserPermissionsDialogTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AdminPanelSeeder::class);
    }

    private function manager(string ...$extra): User
    {
        return $this->userWith('visualizar_permissao', 'gerenciar_permissoes_usuario', ...$extra);
    }

    public function test_the_dialog_opens_over_the_user_list(): void
    {
        $target = $this->userWith('visualizar_usuario');

        $this->actingAs($this->manager('visualizar_usuario'))
            ->get(route('admin.users.permissions', $target))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->where('form.mode', 'user-permissions')
                ->where('form.origin', 'users')
                ->where('form.user.id', $target->id)
                ->where('form.modules.0.key', 'dashboard')
                ->where('form.modules.3.key', 'users')
                ->where('form.modules.3.view', $this->permission('visualizar_usuario')->id)
                ->where('form.modules.3.permissions.0.code', 'visualizar_usuario')
                ->where('form.modules.3.permissions.0.inProfile', true)
                ->where('form.modules.3.permissions.0.assigned', true)
            );
    }

    public function test_the_same_dialog_opens_through_the_permissions_module(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->manager())
            ->get(route('admin.permissions.users.edit', $target))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/Index')
                ->where('form.mode', 'user-permissions')
                ->where('form.origin', 'permissions')
            );
    }

    public function test_the_users_path_needs_the_users_module(): void
    {
        $this->actingAs($this->manager())
            ->get(route('admin.users.permissions', User::factory()->create()))
            ->assertForbidden();
    }

    public function test_the_action_appears_only_with_both_modules(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->manager('visualizar_usuario'))
            ->get(route('admin.users.index', ['search' => $target->email]))
            ->assertInertia(fn (Assert $page) => $page->where('rows.data.0.can.permissions', true));

        $this->actingAs($this->userWith('visualizar_usuario', 'gerenciar_permissoes_usuario'))
            ->get(route('admin.users.index', ['search' => $target->email]))
            ->assertInertia(fn (Assert $page) => $page->where('rows.data.0.can.permissions', false));
    }

    public function test_saving_returns_to_the_list_of_origin(): void
    {
        $target = User::factory()->create();
        $payload = ['adjustments' => [], 'origin' => 'permissions'];

        $this->actingAs($this->manager())
            ->put(route('admin.users.permissions.update', $target), $payload)
            ->assertRedirect(route('admin.permissions.index'));

        $this->actingAs($this->manager('visualizar_usuario'))
            ->put(route('admin.users.permissions.update', $target), [...$payload, 'origin' => 'users'])
            ->assertRedirect(route('admin.users.index'));
    }

    public function test_an_unknown_origin_is_refused(): void
    {
        $this->actingAs($this->manager())
            ->put(route('admin.users.permissions.update', User::factory()->create()), ['adjustments' => [], 'origin' => 'outra'])
            ->assertSessionHasErrors('origin');
    }

    public function test_saving_records_the_change_in_the_history(): void
    {
        $target = User::factory()->create();
        $code = $this->permission('visualizar_permissao');

        $this->actingAs($this->manager())
            ->put(route('admin.users.permissions.update', $target), ['adjustments' => [
                ['permission_id' => $code->id, 'effect' => 'grant'],
            ]]);

        $this->assertTrue(ChangeHistory::where('table_name', (new UserPermission)->getTable())->exists());
    }

    public function test_an_adjustment_of_a_deleted_permission_is_preserved(): void
    {
        $target = User::factory()->create();
        $deleted = Permission::factory()->create();
        $row = UserPermission::create(['user_id' => $target->id, 'permission_id' => $deleted->id, 'effect' => 'grant']);
        $deleted->delete();

        $this->actingAs($this->manager())
            ->put(route('admin.users.permissions.update', $target), ['adjustments' => []]);

        $this->assertFalse($row->fresh()->trashed());
    }

    public function test_a_module_turned_off_shows_a_notice(): void
    {
        config(['template.install.editor_enabled' => false]);

        $this->actingAs($this->manager())
            ->get(route('admin.permissions.users.edit', User::factory()->create()))
            ->assertInertia(fn (Assert $page) => $page
                ->where('form.modules.4.key', 'environment')
                ->where('form.modules.4.notice', 'Este módulo está desligado por configuração.')
            );
    }

    public function test_a_permission_without_module_goes_to_the_other_group(): void
    {
        Permission::factory()->create(['title' => 'Antiga']);

        $this->actingAs($this->manager())
            ->get(route('admin.permissions.users.edit', User::factory()->create()))
            ->assertInertia(fn (Assert $page) => $page
                ->where('form.modules.5.key', 'other')
                ->where('form.modules.5.label', 'Outras')
                ->where('form.modules.5.permissions.0.title', 'Antiga')
            );
    }

    public function test_the_search_finds_by_name_or_email(): void
    {
        $target = User::factory()->create(['name' => 'Rafaela Souza', 'email' => 'rafa@example.test']);
        User::factory()->create(['name' => 'Outra Pessoa']);

        $this->actingAs($this->manager())
            ->getJson(route('admin.permissions.users.search', ['search' => 'rafa']))
            ->assertOk()
            ->assertExactJson([['id' => $target->id, 'name' => 'Rafaela Souza', 'email' => 'rafa@example.test']]);
    }

    public function test_the_search_is_limited(): void
    {
        User::factory()->count(25)->create();

        $this->actingAs($this->manager())
            ->getJson(route('admin.permissions.users.search'))
            ->assertJsonCount(20);
    }

    public function test_the_search_leaves_out_the_actor_and_administrators(): void
    {
        $actor = $this->manager();
        $administrator = User::factory()->administrator()->create();

        $ids = $this->actingAs($actor)
            ->getJson(route('admin.permissions.users.search'))
            ->json('*.id');

        $this->assertNotContains($actor->id, $ids);
        $this->assertNotContains($administrator->id, $ids);
    }

    public function test_an_administrator_finds_other_administrators(): void
    {
        $other = User::factory()->administrator()->create();

        $ids = $this->actingAs(User::factory()->administrator()->create())
            ->getJson(route('admin.permissions.users.search'))
            ->json('*.id');

        $this->assertContains($other->id, $ids);
    }

    public function test_the_search_needs_the_permission(): void
    {
        $this->actingAs($this->userWith('visualizar_permissao'))
            ->getJson(route('admin.permissions.users.search'))
            ->assertForbidden();
    }
}
