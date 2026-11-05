<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use App\Support\Tables\Definitions\PermissionsTable;
use App\Support\Tables\Definitions\ProfilesTable;
use App\Support\Tables\Definitions\UsersTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class RowAbilitiesTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    public function test_system_profiles_are_not_deletable_even_by_administrators(): void
    {
        $can = (new ProfilesTable)->rowAbilities($this->administrator(), Profile::administrator());

        $this->assertFalse($can['delete']);
        $this->assertTrue($can['update']);
    }

    public function test_common_profile_is_deletable_with_the_permission(): void
    {
        $profile = Profile::factory()->create();

        $this->assertTrue((new ProfilesTable)->rowAbilities($this->userWith('excluir_perfil'), $profile)['delete']);
        $this->assertFalse((new ProfilesTable)->rowAbilities($this->userWith(), $profile)['delete']);
    }

    public function test_system_permissions_are_not_deletable_even_by_administrators(): void
    {
        $permission = Permission::factory()->create();
        $permission->is_system = true;
        $permission->save();

        $this->assertFalse((new PermissionsTable)->rowAbilities($this->administrator(), $permission)['delete']);
    }

    public function test_password_change_is_unavailable_for_inactive_accounts(): void
    {
        $actor = $this->administrator();

        $table = new UsersTable;

        $this->assertTrue($table->rowAbilities($actor, User::factory()->create())['passwordChange']);
        $this->assertFalse($table->rowAbilities($actor, User::factory()->inactive()->create())['passwordChange']);
    }

    public function test_authentication_email_is_only_for_active_and_unconfirmed_accounts(): void
    {
        $actor = $this->administrator();

        $table = new UsersTable;

        $this->assertTrue($table->rowAbilities($actor, User::factory()->unverified()->create())['authenticationEmail']);
        $this->assertFalse($table->rowAbilities($actor, User::factory()->create())['authenticationEmail']);
        $this->assertFalse($table->rowAbilities($actor, User::factory()->unverified()->inactive()->create())['authenticationEmail']);
    }

    public function test_deleted_record_only_offers_restoration(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $can = (new UsersTable)->rowAbilities($this->administrator(), $user);

        $this->assertTrue($can['restore']);
        $this->assertFalse($can['update']);
        $this->assertFalse($can['delete']);
        $this->assertFalse($can['status']);
        $this->assertFalse($can['passwordChange']);
    }

    public function test_active_record_does_not_offer_restoration(): void
    {
        $this->assertFalse((new UsersTable)->rowAbilities($this->administrator(), User::factory()->create())['restore']);
    }

    public function test_links_do_not_hide_the_delete_action(): void
    {
        $profile = Profile::factory()->create();
        User::factory()->withProfile($profile)->create();

        $this->assertTrue((new ProfilesTable)->rowAbilities($this->administrator(), $profile)['delete']);
    }
}
