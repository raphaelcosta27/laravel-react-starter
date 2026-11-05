<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class InlineEditTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    public function test_user_name_is_saved_without_the_other_fields(): void
    {
        Notification::fake();
        $user = User::factory()->withProfile(Profile::factory()->create())->create();

        $this->actingAs($this->userWith('editar_usuario'))
            ->patch("/admin/users/{$user->id}", ['name' => 'Novo'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Novo', $user->fresh()->name);

        Notification::assertNothingSent();
    }

    public function test_profile_name_and_description_are_saved_one_at_a_time(): void
    {
        $profile = Profile::factory()->create(['description' => 'Antes']);
        $actor = $this->userWith('editar_perfil');

        $this->actingAs($actor)->patch("/admin/profiles/{$profile->id}", ['name' => 'Novo'])->assertSessionHasNoErrors();
        $this->assertSame('Antes', $profile->fresh()->description);

        $this->actingAs($actor)->patch("/admin/profiles/{$profile->id}", ['description' => 'Depois'])->assertSessionHasNoErrors();
        $this->assertSame('Novo', $profile->fresh()->name);
        $this->assertSame('Depois', $profile->fresh()->description);
    }

    public function test_permission_title_and_description_are_saved_one_at_a_time(): void
    {
        $permission = Permission::factory()->create(['title' => 'Antes', 'description' => 'Antes']);
        $actor = $this->userWith('editar_permissao');

        $this->actingAs($actor)->patch("/admin/permissions/{$permission->id}", ['title' => 'Novo'])->assertSessionHasNoErrors();
        $this->actingAs($actor)->patch("/admin/permissions/{$permission->id}", ['description' => 'Depois'])->assertSessionHasNoErrors();

        $this->assertSame('Novo', $permission->fresh()->title);
        $this->assertSame('Depois', $permission->fresh()->description);
    }

    public function test_same_validations_of_the_forms(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();
        $profile = Profile::factory()->create();
        $used = Profile::factory()->create(['name' => 'Em uso']);
        $permission = Permission::factory()->create();
        $actor = User::factory()->administrator()->create();

        $this->actingAs($actor)->patch("/admin/users/{$user->id}", ['name' => ''])->assertSessionHasErrors('name');
        $this->actingAs($actor)->patch("/admin/profiles/{$profile->id}", ['name' => $used->name])->assertSessionHasErrors('name');
        $this->actingAs($actor)->patch("/admin/permissions/{$permission->id}", ['title' => ''])->assertSessionHasErrors('title');
    }

    public function test_same_permissions_of_the_forms(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();

        $this->actingAs($this->userWith('visualizar_usuario'))
            ->patch("/admin/users/{$user->id}", ['name' => 'Novo'])
            ->assertForbidden();
    }

    public function test_administrator_target_stays_protected(): void
    {
        $target = User::factory()->administrator()->create();

        $this->actingAs($this->userWith('editar_usuario'))
            ->patch("/admin/users/{$target->id}", ['name' => 'Tomado'])
            ->assertForbidden();

        $this->assertNotSame('Tomado', $target->fresh()->name);
    }

    public function test_own_profile_stays_protected(): void
    {
        $user = $this->userWith('editar_perfil');

        $this->actingAs($user)
            ->patch("/admin/profiles/{$user->profile_id}", ['name' => 'Tomado'])
            ->assertForbidden();
    }
}
