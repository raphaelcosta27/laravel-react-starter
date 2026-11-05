<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class TableExtrasTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function tabs(string $url, User $actor): array
    {
        $tabs = [];

        $this->actingAs($actor)->get($url)->assertInertia(function (Assert $page) use (&$tabs) {
            $tabs = collect($page->toArray()['props']['tabs'])->pluck('count', 'key')->all();
        });

        return $tabs;
    }

    public function test_users_have_the_four_tabs_with_counts(): void
    {
        $actor = User::factory()->administrator()->create();
        User::factory()->inactive()->create();
        User::factory()->create()->delete();

        $this->assertSame(
            ['all' => 2, 'active' => 1, 'inactive' => 1, 'trashed' => 1],
            $this->tabs('/admin/users', $actor),
        );
    }

    public function test_profiles_have_active_and_deleted_tabs(): void
    {
        Profile::factory()->create();
        Profile::factory()->create()->delete();

        $tabs = $this->tabs('/admin/profiles', User::factory()->administrator()->create());

        $this->assertSame(['active', 'trashed'], array_keys($tabs));
        $this->assertSame(1, $tabs['trashed']);
    }

    public function test_permissions_have_active_and_deleted_tabs(): void
    {
        Permission::factory()->create();
        Permission::factory()->create()->delete();

        $this->assertSame(
            ['active' => 1, 'trashed' => 1],
            $this->tabs('/admin/permissions', User::factory()->administrator()->create()),
        );
    }

    public function test_deleted_tab_requires_the_restoration_permission(): void
    {
        $this->assertArrayNotHasKey('trashed', $this->tabs('/admin/users', $this->userWith('visualizar_usuario')));
        $this->assertArrayNotHasKey('trashed', $this->tabs('/admin/profiles', $this->userWith('visualizar_perfil')));
        $this->assertArrayNotHasKey('trashed', $this->tabs('/admin/permissions', $this->userWith('visualizar_permissao')));

        $this->assertArrayHasKey('trashed', $this->tabs('/admin/users', $this->userWith('visualizar_usuario', 'restaurar_usuario')));
    }

    public function test_deleted_tab_without_the_permission_falls_back_to_the_default(): void
    {
        User::factory()->create(['name' => 'Excluída'])->delete();

        $this->actingAs($this->userWith('visualizar_usuario'))
            ->get('/admin/users?tab=trashed')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('query.tab', 'all')->etc());
    }

    public function test_deleted_tab_shows_the_deleted_records(): void
    {
        User::factory()->create(['name' => 'Excluída'])->delete();

        $this->actingAs(User::factory()->administrator()->create())
            ->get('/admin/users?tab=trashed')
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows.data', 1)
                ->where('rows.data.0.name', 'Excluída')
                ->where('rows.data.0.can.restore', true)
                ->etc());
    }

    public function test_profile_totals_use_the_whole_filter(): void
    {
        $profile = Profile::factory()->create(['name' => 'Financeiro']);
        User::factory()->count(3)->withProfile($profile)->create();
        User::factory()->count(2)->withProfile(Profile::factory()->create(['name' => 'Financeiro 2']))->create();
        User::factory()->withProfile(Profile::factory()->create(['name' => 'Outro']))->create();

        $this->actingAs($this->userWith('visualizar_perfil'))
            ->get('/admin/profiles?search=Financeiro&per_page=10')
            ->assertInertia(fn (Assert $page) => $page->where('summaries.users_count.sum', 5)->etc());
    }

    public function test_listing_of_profiles_brings_the_permissions_for_the_detail_row(): void
    {
        $profile = $this->profileWith('gerar_relatorio');

        $this->actingAs(User::factory()->administrator()->create())
            ->get('/admin/profiles?search='.urlencode($profile->name))
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.data.0.permissions.0.code', 'gerar_relatorio')
                ->etc());
    }

    public function test_bulk_abilities_follow_the_permissions(): void
    {
        $this->actingAs($this->userWith('visualizar_usuario', 'excluir_usuario'))
            ->get('/admin/users')
            ->assertInertia(fn (Assert $page) => $page->where('abilities.bulk', ['delete'])->etc());
    }
}
