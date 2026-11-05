<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    /** @return array<string, array{0: string, 1: string}> */
    public static function areas(): array
    {
        return [
            'perfis' => ['/admin/profiles', 'visualizar_perfil'],
            'permissões' => ['/admin/permissions', 'visualizar_permissao'],
            'usuários' => ['/admin/users', 'visualizar_usuario'],
        ];
    }

    #[DataProvider('areas')]
    public function test_administrator_opens_the_area(string $url): void
    {
        $this->actingAs(User::factory()->administrator()->create())->get($url)->assertOk();
    }

    #[DataProvider('areas')]
    public function test_user_without_permission_is_refused(string $url): void
    {
        $this->actingAs($this->userWith())->get($url)->assertForbidden();
    }

    #[DataProvider('areas')]
    public function test_user_with_the_permission_of_the_area_is_released(string $url, string $code): void
    {
        $this->actingAs($this->userWith($code))->get($url)->assertOk();
    }

    #[DataProvider('areas')]
    public function test_guest_is_redirected_to_login(string $url): void
    {
        $this->get($url)->assertRedirect(route('login'));
    }

    #[DataProvider('areas')]
    public function test_unconfirmed_account_is_redirected(string $url): void
    {
        $user = User::factory()->administrator()->unverified()->create();

        $this->actingAs($user)->get($url)->assertRedirect(route('verification.notice'));
    }

    #[DataProvider('areas')]
    public function test_account_with_pending_password_change_is_redirected(string $url): void
    {
        $user = User::factory()->administrator()->mustChangePassword()->create();

        $this->actingAs($user)->get($url)->assertRedirect(route('password.change'));
    }

    public function test_profiles_list_shows_name_description_and_user_count(): void
    {
        $profile = Profile::factory()->create(['name' => 'Financeiro', 'description' => 'Equipe']);
        User::factory()->count(2)->withProfile($profile)->create();
        User::factory()->withProfile($profile)->create()->delete();

        $this->actingAs($this->userWith('visualizar_perfil'))
            ->get('/admin/profiles?search=Financeiro')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Profiles/Index')
                ->where('rows.data.0.name', 'Financeiro')
                ->where('rows.data.0.description', 'Equipe')
                ->where('rows.data.0.users_count', 2)
                ->etc());
    }

    public function test_permissions_list_shows_title_code_and_description(): void
    {
        Permission::factory()->create([
            'title' => 'Gerar Relatório',
            'code' => 'gerar_relatorio',
            'description' => 'Permite gerar',
        ]);

        $this->actingAs($this->userWith('visualizar_permissao'))
            ->get('/admin/permissions?search=gerar_relatorio')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/Index')
                ->where('rows.data.0.title', 'Gerar Relatório')
                ->where('rows.data.0.code', 'gerar_relatorio')
                ->where('rows.data.0.description', 'Permite gerar')
                ->etc());
    }

    public function test_users_list_shows_name_email_profile_status_and_confirmation(): void
    {
        $profile = Profile::factory()->create(['name' => 'Financeiro']);
        User::factory()->withProfile($profile)->inactive()->unverified()->create([
            'name' => 'Zeca',
            'email' => 'zeca@example.com',
        ]);

        $this->actingAs($this->userWith('visualizar_usuario'))
            ->get('/admin/users?search=zeca')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->where('rows.data.0.name', 'Zeca')
                ->where('rows.data.0.email', 'zeca@example.com')
                ->where('rows.data.0.profile_name', 'Financeiro')
                ->where('rows.data.0.is_active', false)
                ->where('rows.data.0.confirmed', false)
                ->etc());
    }

    public function test_listing_does_not_expose_sensitive_fields(): void
    {
        $response = $this->actingAs(User::factory()->administrator()->create())->get('/admin/users');

        $response->assertInertia(fn (Assert $page) => $page
            ->missing('rows.data.0.password')
            ->missing('rows.data.0.remember_token')
            ->etc());
    }
}
