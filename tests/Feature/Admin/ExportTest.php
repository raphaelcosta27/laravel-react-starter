<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create(['name' => 'Administradora']);
    }

    private function csv(string $query, ?User $actor = null): string
    {
        return $this->actingAs($actor ?? $this->administrator())
            ->get('/admin/users/export?format=csv&'.$query)
            ->assertOk()
            ->streamedContent();
    }

    public function test_exports_every_record_of_the_filter_not_only_the_page(): void
    {
        User::factory()->count(30)->create(['name' => 'Ana']);
        User::factory()->create(['name' => 'Bia']);

        $content = $this->csv('search=Ana&per_page=10&columns[]=name');

        $this->assertSame(30, substr_count($content, 'Ana'));
        $this->assertStringNotContainsString('Bia', $content);
    }

    public function test_exports_only_allowed_columns_in_the_requested_order(): void
    {
        User::factory()->create(['name' => 'Ana', 'email' => 'ana@example.com']);

        $content = $this->csv('search=ana@&columns[]=email&columns[]=name&columns[]=password');

        $this->assertStringContainsString('E-mail;Nome', $content);
        $this->assertStringContainsString('ana@example.com;Ana', $content);
        $this->assertStringNotContainsString('$2y$', $content);
    }

    public function test_ids_restrict_to_the_selected_records(): void
    {
        $first = User::factory()->create(['name' => 'Ana']);
        User::factory()->create(['name' => 'Bia']);

        $content = $this->csv("columns[]=name&ids[]={$first->id}");

        $this->assertStringContainsString('Ana', $content);
        $this->assertStringNotContainsString('Bia', $content);
    }

    public function test_exports_labels_instead_of_raw_values(): void
    {
        $profile = Profile::factory()->create(['name' => 'Financeiro']);
        User::factory()->withProfile($profile)->inactive()->unverified()->create(['email' => 'ana@example.com']);

        $content = $this->csv('search=ana@&columns[]=profile_id&columns[]=is_active&columns[]=confirmed');

        $this->assertStringContainsString('Financeiro;Inativo;Pendente', $content);
    }

    public function test_exports_xlsx(): void
    {
        User::factory()->create();

        $response = $this->actingAs($this->administrator())
            ->get('/admin/users/export?format=xlsx&columns[]=name')
            ->assertOk();

        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
    }

    public function test_requires_exportar_dados(): void
    {
        $this->actingAs($this->userWith('visualizar_usuario'))
            ->get('/admin/users/export?format=csv')
            ->assertForbidden();
    }

    public function test_requires_the_view_permission_of_the_area(): void
    {
        $this->actingAs($this->userWith('exportar_dados'))
            ->get('/admin/users/export?format=csv')
            ->assertForbidden();
    }

    public function test_every_area_has_its_export(): void
    {
        $actor = $this->administrator();

        $this->actingAs($actor)->get('/admin/profiles/export?format=csv&columns[]=name')->assertSessionHasNoErrors();
        $this->actingAs($actor)->get('/admin/permissions/export?format=csv&columns[]=title')->assertSessionHasNoErrors();
    }

    public function test_no_records_gives_a_warning_and_no_file(): void
    {
        $this->actingAs($this->administrator())
            ->from('/admin/users')
            ->get('/admin/users/export?format=csv&search=ninguem')
            ->assertRedirect('/admin/users')
            ->assertSessionHas('flash.type', 'warning');
    }

    public function test_deleted_records_do_not_leak_without_the_restoration_permission(): void
    {
        User::factory()->create(['name' => 'Excluída'])->delete();
        User::factory()->create(['name' => 'Ativa']);

        $content = $this->csv(
            'tab=trashed&columns[]=name',
            $this->userWith('visualizar_usuario', 'exportar_dados'),
        );

        $this->assertStringNotContainsString('Excluída', $content);
        $this->assertStringContainsString('Ativa', $content);
    }

    public function test_deleted_records_are_exported_with_the_restoration_permission(): void
    {
        User::factory()->create(['name' => 'Excluída'])->delete();

        $content = $this->csv(
            'tab=trashed&columns[]=name',
            $this->userWith('visualizar_usuario', 'exportar_dados', 'restaurar_usuario'),
        );

        $this->assertStringContainsString('Excluída', $content);
    }
}
