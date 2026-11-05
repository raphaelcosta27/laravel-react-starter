<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\AdminPanelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private const MODEL = [
        'title' => 'Cadastrar Relatórios',
        'code' => 'cadastrar_relatorio',
        'description' => 'Permissão permite o cadastro de novos relatórios',
    ];

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    public function test_creates_a_permission_following_the_model(): void
    {
        $this->actingAs($this->administrator())
            ->post('/admin/permissions', self::MODEL)
            ->assertRedirect('/admin/permissions')
            ->assertSessionHas('flash.type', 'success');

        $this->assertDatabaseHas('permissions', [...self::MODEL, 'is_system' => false]);
    }

    public function test_code_of_the_seeded_permission_is_refused(): void
    {
        $this->seed(AdminPanelSeeder::class);

        $this->actingAs($this->administrator())
            ->post('/admin/permissions', [...self::MODEL, 'code' => 'cadastrar_usuario'])
            ->assertSessionHasErrors('code');
    }

    /** @return array<string, array{0: string}> */
    public static function requiredFields(): array
    {
        return ['título' => ['title'], 'código' => ['code'], 'descrição' => ['description']];
    }

    #[DataProvider('requiredFields')]
    public function test_field_is_required(string $field): void
    {
        $this->actingAs($this->administrator())
            ->post('/admin/permissions', [...self::MODEL, $field => ''])
            ->assertSessionHasErrors($field);
    }

    /** @return array<string, array{0: string}> */
    public static function invalidCodes(): array
    {
        return [
            'maiúscula' => ['Cadastrar_usuario'],
            'acento' => ['cadastrar_usuário'],
            'começa com número' => ['1cadastrar'],
            'começa com sublinhado' => ['_cadastrar'],
            'espaço' => ['cadastrar usuario'],
            'hífen' => ['cadastrar-usuario'],
        ];
    }

    #[DataProvider('invalidCodes')]
    public function test_code_outside_the_format_is_refused(string $code): void
    {
        $this->actingAs($this->administrator())
            ->post('/admin/permissions', [...self::MODEL, 'code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_used_code_is_refused(): void
    {
        Permission::factory()->create(['code' => 'cadastrar_relatorio']);

        $this->actingAs($this->administrator())
            ->post('/admin/permissions', self::MODEL)
            ->assertSessionHasErrors('code');
    }

    public function test_code_of_a_deleted_permission_is_accepted(): void
    {
        Permission::factory()->create(['code' => 'cadastrar_relatorio'])->delete();

        $this->actingAs($this->administrator())
            ->post('/admin/permissions', self::MODEL)
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Permission::where('code', 'cadastrar_relatorio')->count());
    }

    public function test_edit_changes_title_and_description_and_ignores_the_code(): void
    {
        $permission = Permission::factory()->create(['code' => 'cadastrar_relatorio']);

        $this->actingAs($this->administrator())
            ->patch("/admin/permissions/{$permission->id}", [
                'title' => 'Novo',
                'description' => 'Nova descrição',
                'code' => 'outro_codigo',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'title' => 'Novo',
            'description' => 'Nova descrição',
            'code' => 'cadastrar_relatorio',
        ]);
    }

    public function test_edit_screen_receives_the_permission(): void
    {
        $permission = Permission::factory()->create();

        $this->actingAs($this->administrator())
            ->get("/admin/permissions/{$permission->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/Index')
                ->where('form.mode', 'edit')
                ->where('form.record.code', $permission->code)
                ->etc());
    }

    public function test_create_screen_opens(): void
    {
        $this->actingAs($this->administrator())
            ->get('/admin/permissions/create')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/Index')
                ->where('form.mode', 'create')
                ->where('form.record', null)
                ->etc());
    }

    public function test_forms_require_the_listing_permission(): void
    {
        $permission = Permission::factory()->create();
        $user = $this->userWith('cadastrar_permissao', 'editar_permissao');

        $this->actingAs($user)->get('/admin/permissions/create')->assertForbidden();
        $this->actingAs($user)->get("/admin/permissions/{$permission->id}/edit")->assertForbidden();
    }

    public function test_each_route_requires_its_permission(): void
    {
        $permission = Permission::factory()->create();
        $user = $this->userWith('visualizar_permissao');

        $this->actingAs($user)->get('/admin/permissions/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/permissions', self::MODEL)->assertForbidden();
        $this->actingAs($user)->get("/admin/permissions/{$permission->id}/edit")->assertForbidden();
        $this->actingAs($user)->patch("/admin/permissions/{$permission->id}", ['title' => 'x'])->assertForbidden();
        $this->actingAs($user)->delete("/admin/permissions/{$permission->id}")->assertForbidden();
    }

    public function test_user_with_the_permission_is_released(): void
    {
        $this->actingAs($this->userWith('cadastrar_permissao'))
            ->post('/admin/permissions', self::MODEL)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('permissions', ['code' => 'cadastrar_relatorio']);
    }

    public function test_deleted_permission_answers_not_found(): void
    {
        $permission = Permission::factory()->create();
        $permission->delete();

        $this->actingAs($this->administrator())
            ->get("/admin/permissions/{$permission->id}/edit")
            ->assertNotFound();
    }
}
