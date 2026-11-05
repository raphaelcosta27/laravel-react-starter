<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use App\Notifications\WelcomeUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    public function test_creates_a_user_without_informing_a_password(): void
    {
        $profile = Profile::factory()->create();

        $this->actingAs($this->administrator())
            ->post('/admin/users', [
                'name' => 'Ana',
                'email' => 'ana@example.com',
                'profile_id' => $profile->id,
                'password' => 'ignorada',
            ])
            ->assertRedirect('/admin/users')
            ->assertSessionHas('flash.type', 'success');

        $user = User::firstWhere('email', 'ana@example.com');

        $this->assertSame($profile->id, $user->profile_id);
        $this->assertFalse(Hash::check('ignorada', $user->password));
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->must_change_password);
        $this->assertNotNull($user->temporary_password_expires_at);

        Notification::assertSentTo($user, WelcomeUser::class, fn (WelcomeUser $notification) => $notification->temporaryPassword !== null);
    }

    public function test_name_email_and_profile_are_required(): void
    {
        $this->actingAs($this->administrator())
            ->post('/admin/users', [])
            ->assertSessionHasErrors(['name', 'email', 'profile_id']);
    }

    public function test_used_email_is_refused(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->actingAs($this->administrator())
            ->post('/admin/users', ['name' => 'Ana', 'email' => 'ana@example.com', 'profile_id' => Profile::default()->id])
            ->assertSessionHasErrors('email');
    }

    public function test_deleted_profile_cannot_be_chosen(): void
    {
        $profile = Profile::factory()->create();
        $profile->delete();

        $this->actingAs($this->administrator())
            ->post('/admin/users', ['name' => 'Ana', 'email' => 'ana@example.com', 'profile_id' => $profile->id])
            ->assertSessionHasErrors('profile_id');
    }

    public function test_edits_name_and_profile(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();
        $profile = Profile::factory()->create();

        $this->actingAs($this->administrator())
            ->patch("/admin/users/{$user->id}", ['name' => 'Novo', 'email' => $user->email, 'profile_id' => $profile->id])
            ->assertSessionHasNoErrors();

        $this->assertSame('Novo', $user->fresh()->name);
        $this->assertSame($profile->id, $user->fresh()->profile_id);
        $this->assertNotNull($user->fresh()->email_verified_at);

        Notification::assertNothingSent();
    }

    public function test_edit_accepts_partial_data(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();

        $this->actingAs($this->administrator())
            ->patch("/admin/users/{$user->id}", ['name' => 'Só o nome'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Só o nome', $user->fresh()->name);
    }

    public function test_changing_the_email_requires_confirmation_and_keeps_the_password(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();
        $password = $user->password;

        $this->actingAs($this->administrator())
            ->patch("/admin/users/{$user->id}", ['email' => 'novo@example.com'])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('novo@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertSame($password, $user->password);
        $this->assertNull($user->temporary_password_expires_at);

        Notification::assertSentTo($user, WelcomeUser::class, fn (WelcomeUser $notification) => $notification->temporaryPassword === null);
    }

    public function test_email_of_a_deleted_account_is_refused_on_edit(): void
    {
        User::factory()->create(['email' => 'excluida@example.com'])->delete();
        $user = User::factory()->withProfile(Profile::factory()->create())->create();

        $this->actingAs($this->administrator())
            ->patch("/admin/users/{$user->id}", ['email' => 'excluida@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_deletes_a_user_keeping_the_record(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();

        $this->actingAs($this->administrator())
            ->delete("/admin/users/{$user->id}")
            ->assertSessionHas('flash.type', 'success');

        $this->assertSoftDeleted($user);
    }

    public function test_create_screen_offers_the_active_profiles(): void
    {
        $deleted = Profile::factory()->create();
        $deleted->delete();
        $profile = Profile::factory()->create(['name' => 'Financeiro']);

        $this->actingAs($this->administrator())
            ->get('/admin/users/create')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Form')
                ->where('user', null)
                ->has('profileOptions', 2)
                ->etc());

        $this->assertNotNull($profile);
    }

    public function test_each_route_requires_its_permission(): void
    {
        $target = User::factory()->withProfile(Profile::factory()->create())->create();
        $user = $this->userWith('visualizar_usuario');

        $this->actingAs($user)->get('/admin/users/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/users', [])->assertForbidden();
        $this->actingAs($user)->get("/admin/users/{$target->id}/edit")->assertForbidden();
        $this->actingAs($user)->patch("/admin/users/{$target->id}", ['name' => 'x'])->assertForbidden();
        $this->actingAs($user)->patch("/admin/users/{$target->id}/status", ['is_active' => false])->assertForbidden();
        $this->actingAs($user)->delete("/admin/users/{$target->id}")->assertForbidden();
    }

    public function test_row_abilities_follow_the_policy(): void
    {
        $administrator = $this->administrator();
        $common = User::factory()->withProfile(Profile::factory()->create())->create(['name' => 'Comum']);

        $this->actingAs($this->userWith('visualizar_usuario', 'editar_usuario'))
            ->get('/admin/users?search='.$administrator->email)
            ->assertInertia(fn (Assert $page) => $page->where('rows.data.0.can.update', false)->etc());

        $this->actingAs($this->userWith('visualizar_usuario', 'editar_usuario'))
            ->get('/admin/users?search='.$common->email)
            ->assertInertia(fn (Assert $page) => $page->where('rows.data.0.can.update', true)->etc());
    }
}
