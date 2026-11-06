<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserPermission;
use App\Notifications\WelcomeUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class ReactivateUserTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config(['template.registration_enabled' => true]);
    }

    private function deletedUser(array $attributes = []): User
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create([
            'email' => 'ana@example.com',
            ...$attributes,
        ]);
        $user->delete();

        return $user;
    }

    private function register(): void
    {
        $this->post('/register', [
            'name' => 'Ana Nova',
            'email' => 'ana@example.com',
            'password' => 'Senha-digitada-1',
            'password_confirmation' => 'Senha-digitada-1',
        ]);
    }

    public function test_panel_reactivates_the_same_account_with_the_form_data(): void
    {
        $user = $this->deletedUser();
        $profile = Profile::factory()->create();
        User::factory()->administrator()->create();

        $this->actingAs(User::factory()->administrator()->create())
            ->post('/admin/users', ['name' => 'Ana Nova', 'email' => 'ana@example.com', 'profile_id' => $profile->id])
            ->assertSessionHas('flash.type', 'warning')
            ->assertSessionHasNoErrors();

        $fresh = User::find($user->id);

        $this->assertSame(1, User::withTrashed()->where('email', 'ana@example.com')->count());
        $this->assertNotNull($fresh);
        $this->assertSame('Ana Nova', $fresh->name);
        $this->assertSame($profile->id, $fresh->profile_id);
    }

    public function test_reactivation_follows_the_creation_rules(): void
    {
        $user = $this->deletedUser(['is_active' => false, 'must_change_password' => false]);

        $this->register();

        $fresh = User::find($user->id);

        $this->assertTrue($fresh->is_active);
        $this->assertNull($fresh->email_verified_at);
        $this->assertTrue($fresh->must_change_password);
        $this->assertNotNull($fresh->temporary_password_expires_at);

        Notification::assertSentTo($fresh, WelcomeUser::class, fn (WelcomeUser $notification) => $notification->temporaryPassword !== null);
    }

    public function test_public_registration_uses_the_default_profile_without_automatic_login(): void
    {
        $user = $this->deletedUser();

        $this->post('/register', [
            'name' => 'Ana Nova',
            'email' => 'ana@example.com',
            'password' => 'Senha-digitada-1',
            'password_confirmation' => 'Senha-digitada-1',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertGuest();

        $fresh = User::find($user->id);

        $this->assertSame('Ana Nova', $fresh->name);
        $this->assertSame(Profile::default()->id, $fresh->profile_id);
    }

    public function test_typed_password_is_discarded(): void
    {
        $user = $this->deletedUser();

        $this->register();

        $this->assertFalse(Hash::check('Senha-digitada-1', User::find($user->id)->password));
    }

    public function test_individual_adjustments_do_not_come_back(): void
    {
        $user = $this->deletedUser();
        UserPermission::create([
            'user_id' => $user->id,
            'permission_id' => $this->permission('gerar_relatorio')->id,
            'effect' => 'grant',
        ]);

        $this->register();

        $this->assertFalse(User::find($user->id)->can('gerar_relatorio'));
        $this->assertSame(1, UserPermission::withTrashed()->where('user_id', $user->id)->count());
    }

    public function test_former_administrator_comes_back_without_the_profile(): void
    {
        User::factory()->administrator()->create();
        $user = User::factory()->administrator()->create(['email' => 'ana@example.com']);
        $user->delete();

        $this->register();

        $this->assertFalse(User::find($user->id)->isAdministrator());
    }

    public function test_inactive_account_is_refused_as_used_email(): void
    {
        User::factory()->inactive()->create(['email' => 'ana@example.com']);

        $this->post('/register', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'Senha-digitada-1',
            'password_confirmation' => 'Senha-digitada-1',
        ])->assertSessionHasErrors('email');

        $this->actingAs(User::factory()->administrator()->create())
            ->post('/admin/users', ['name' => 'Ana', 'email' => 'ana@example.com', 'profile_id' => Profile::default()->id])
            ->assertSessionHasErrors('email');
    }
}
