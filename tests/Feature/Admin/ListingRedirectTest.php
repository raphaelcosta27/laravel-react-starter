<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class ListingRedirectTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    public function test_saving_keeps_the_query_of_the_referer(): void
    {
        $permission = Permission::factory()->create();

        $this->actingAs($this->administrator())
            ->from("/admin/permissions/{$permission->id}/edit?search=ana&page=2")
            ->patch("/admin/permissions/{$permission->id}", ['title' => 'Novo', 'description' => 'Nova'])
            ->assertRedirect('/admin/permissions?search=ana&page=2');
    }

    public function test_nested_filters_survive_the_redirect(): void
    {
        $profile = Profile::factory()->create();

        $this->actingAs($this->administrator())
            ->from("/admin/profiles/{$profile->id}/edit?filter%5Bname%5D=fin&sort=-name")
            ->patch("/admin/profiles/{$profile->id}", ['name' => 'Financeiro'])
            ->assertRedirect(route('admin.profiles.index', ['filter' => ['name' => 'fin'], 'sort' => '-name']));
    }

    public function test_the_host_and_path_of_the_referer_are_ignored(): void
    {
        $permission = Permission::factory()->create();

        $this->actingAs($this->administrator())
            ->withHeader('referer', 'https://attacker.example/phishing?search=ana')
            ->patch("/admin/permissions/{$permission->id}", ['title' => 'Novo', 'description' => 'Nova'])
            ->assertRedirect(route('admin.permissions.index', ['search' => 'ana']));
    }

    public function test_it_works_without_a_referer(): void
    {
        $permission = Permission::factory()->create();

        $this->actingAs($this->administrator())
            ->patch("/admin/permissions/{$permission->id}", ['title' => 'Novo', 'description' => 'Nova'])
            ->assertRedirect(route('admin.permissions.index'));
    }

    public function test_creating_keeps_the_query_of_the_referer(): void
    {
        $this->actingAs($this->administrator())
            ->from('/admin/profiles/create?search=ana')
            ->post('/admin/profiles', ['name' => 'Financeiro', 'permissions' => []])
            ->assertRedirect('/admin/profiles?search=ana');
    }

    public function test_inline_edit_returns_to_the_filtered_listing(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();

        $this->actingAs($this->administrator())
            ->from('/admin/users?search=ana&page=3')
            ->patch("/admin/users/{$user->id}", ['name' => 'Nome Novo'])
            ->assertRedirect('/admin/users?search=ana&page=3');
    }

    public function test_validation_errors_return_to_the_open_form(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();
        $url = "/admin/users/{$user->id}/edit?search=ana";

        $this->actingAs($this->administrator())
            ->from($url)
            ->patch("/admin/users/{$user->id}", ['email' => 'invalido'])
            ->assertRedirect($url)
            ->assertSessionHasErrors('email');
    }

    public function test_a_partial_visit_for_the_form_does_not_query_the_table(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();

        $this->actingAs($this->administrator());

        DB::enableQueryLog();

        $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
            'X-Inertia-Partial-Component' => 'Admin/Users/Index',
            'X-Inertia-Partial-Data' => 'form',
        ])->get("/admin/users/{$user->id}/edit")
            ->assertOk()
            ->assertJsonPath('props.form.record.id', $user->id)
            ->assertJsonMissingPath('props.rows');

        $counts = collect(DB::getQueryLog())->pluck('query')
            ->filter(fn (string $query) => str_contains($query, 'count('));

        $this->assertCount(0, $counts);
    }
}
