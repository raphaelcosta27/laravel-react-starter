<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class UserProtectionTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    public function test_non_administrator_cannot_create_an_administrator(): void
    {
        $this->actingAs($this->userWith('cadastrar_usuario'))
            ->post('/admin/users', [
                'name' => 'Ana',
                'email' => 'ana@example.com',
                'profile_id' => Profile::administrator()->id,
            ])
            ->assertForbidden();

        $this->assertNull(User::firstWhere('email', 'ana@example.com'));
    }

    public function test_non_administrator_cannot_assign_the_master_profile(): void
    {
        $target = User::factory()->withProfile(Profile::factory()->create())->create();

        $this->actingAs($this->userWith('editar_usuario'))
            ->patch("/admin/users/{$target->id}", ['profile_id' => Profile::administrator()->id])
            ->assertForbidden();

        $this->assertFalse($target->fresh()->isAdministrator());
    }

    public function test_non_administrator_cannot_act_on_an_administrator(): void
    {
        $target = User::factory()->administrator()->create();
        $user = $this->userWith('editar_usuario', 'alterar_situacao_usuario', 'excluir_usuario');

        $this->actingAs($user)->get("/admin/users/{$target->id}/edit")->assertForbidden();
        $this->actingAs($user)->patch("/admin/users/{$target->id}", ['name' => 'Tomado'])->assertForbidden();
        $this->actingAs($user)->patch("/admin/users/{$target->id}/status", ['is_active' => false])->assertForbidden();
        $this->actingAs($user)->delete("/admin/users/{$target->id}")->assertForbidden();

        $this->assertNotSame('Tomado', $target->fresh()->name);
        $this->assertTrue($target->fresh()->is_active);
        $this->assertNotSoftDeleted($target);
    }

    public function test_non_administrator_cannot_change_their_own_profile(): void
    {
        $user = $this->userWith('editar_usuario');
        $profile = Profile::factory()->create();

        $this->actingAs($user)
            ->patch("/admin/users/{$user->id}", ['profile_id' => $profile->id])
            ->assertForbidden();

        $this->assertNotSame($profile->id, $user->fresh()->profile_id);
    }

    public function test_non_administrator_can_keep_their_profile_and_edit_their_name(): void
    {
        $user = $this->userWith('editar_usuario');

        $this->actingAs($user)
            ->patch("/admin/users/{$user->id}", ['name' => 'Novo', 'profile_id' => $user->profile_id])
            ->assertSessionHasNoErrors();

        $this->assertSame('Novo', $user->fresh()->name);
    }

    public function test_non_administrator_manages_common_users(): void
    {
        Notification::fake();
        $target = User::factory()->withProfile(Profile::factory()->create())->create();
        $profile = Profile::factory()->create();

        $this->actingAs($this->userWith('editar_usuario'))
            ->patch("/admin/users/{$target->id}", ['profile_id' => $profile->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($profile->id, $target->fresh()->profile_id);
    }

    public function test_administrator_can_inactivate_or_delete_their_own_account_if_another_remains(): void
    {
        $user = User::factory()->administrator()->create();
        User::factory()->administrator()->create();

        $this->actingAs($user)->delete("/admin/users/{$user->id}")->assertSessionHasNoErrors();

        $this->assertSoftDeleted($user);
    }

    public function test_master_profile_is_hidden_from_non_administrators_on_the_form(): void
    {
        $this->actingAs($this->userWith('cadastrar_usuario'))
            ->get('/admin/users/create')
            ->assertInertia(fn ($page) => $page->where(
                'profileOptions',
                fn ($options) => collect($options)->doesntContain('value', (string) Profile::administrator()->id),
            )->etc());
    }
}
