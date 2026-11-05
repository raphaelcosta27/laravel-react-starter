<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_name_allows_deleted_and_active_profiles_with_the_same_name(): void
    {
        $deleted = Profile::factory()->create(['name' => 'Financeiro']);
        $deleted->delete();

        $active = Profile::factory()->create(['name' => 'Financeiro']);

        $this->assertNotSame($deleted->id, $active->id);
    }

    public function test_active_name_refuses_two_active_profiles_with_the_same_name(): void
    {
        Profile::factory()->create(['name' => 'Financeiro']);

        $this->expectException(QueryException::class);

        Profile::factory()->create(['name' => 'Financeiro']);
    }

    public function test_restoring_a_profile_with_a_free_name_works(): void
    {
        $profile = Profile::factory()->create(['name' => 'Financeiro']);
        $profile->delete();

        $profile->restore();

        $this->assertNotSoftDeleted($profile);
    }

    public function test_restoring_a_profile_with_a_name_in_use_is_refused_by_the_database(): void
    {
        $profile = Profile::factory()->create(['name' => 'Financeiro']);
        $profile->delete();
        Profile::factory()->create(['name' => 'Financeiro']);

        $this->expectException(QueryException::class);

        $profile->restore();
    }

    public function test_active_code_allows_deleted_and_active_permissions_with_the_same_code(): void
    {
        $deleted = Permission::factory()->create(['code' => 'gerar_relatorio']);
        $deleted->delete();

        $active = Permission::factory()->create(['code' => 'gerar_relatorio']);

        $this->assertNotSame($deleted->id, $active->id);
    }

    public function test_active_code_refuses_two_active_permissions_with_the_same_code(): void
    {
        Permission::factory()->create(['code' => 'gerar_relatorio']);

        $this->expectException(QueryException::class);

        Permission::factory()->create(['code' => 'gerar_relatorio']);
    }

    public function test_users_email_refuses_the_email_of_a_deleted_account(): void
    {
        $user = User::factory()->create(['email' => 'ana@example.com']);
        $user->delete();

        $this->expectException(QueryException::class);

        User::factory()->create(['email' => 'ana@example.com']);
    }

    public function test_foreign_keys_do_not_delete_in_cascade(): void
    {
        $profile = Profile::factory()->create();
        User::factory()->withProfile($profile)->create();

        $this->expectException(QueryException::class);

        DB::table('profiles')->where('id', $profile->id)->delete();
    }
}
