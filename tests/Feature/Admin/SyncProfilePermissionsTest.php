<?php

namespace Tests\Feature\Admin;

use App\Actions\Admin\SyncProfilePermissions;
use App\Models\Profile;
use App\Models\ProfilePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class SyncProfilePermissionsTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function sync(Profile $profile, array $ids): void
    {
        app(SyncProfilePermissions::class)->handle($profile, $ids);
    }

    public function test_removing_fills_deleted_at_and_keeps_the_row(): void
    {
        $profile = $this->profileWith('gerar_relatorio');

        $this->sync($profile, []);

        $this->assertSame(0, ProfilePermission::where('profile_id', $profile->id)->count());
        $this->assertSame(1, ProfilePermission::withTrashed()->where('profile_id', $profile->id)->count());
    }

    public function test_adding_again_restores_the_same_row(): void
    {
        $profile = $this->profileWith('gerar_relatorio');
        $link = ProfilePermission::where('profile_id', $profile->id)->first();

        $this->sync($profile, []);
        $this->sync($profile, [$link->permission_id]);

        $this->assertNotSoftDeleted($link);
        $this->assertSame(1, ProfilePermission::withTrashed()->where('profile_id', $profile->id)->count());
    }

    public function test_adds_new_links_and_keeps_existing_ones(): void
    {
        $profile = $this->profileWith('gerar_relatorio');
        $link = ProfilePermission::where('profile_id', $profile->id)->first();
        $new = $this->permission('exportar_relatorio');

        $this->sync($profile, [$link->permission_id, $new->id]);

        $this->assertSame(2, ProfilePermission::where('profile_id', $profile->id)->count());
        $this->assertNotSoftDeleted($link);
    }

    public function test_change_is_valid_on_the_next_request(): void
    {
        $user = $this->userWith();
        $profile = Profile::find($user->profile_id);

        $this->assertFalse(User::find($user->id)->can('gerar_relatorio'));

        $this->sync($profile, [$this->permission('gerar_relatorio')->id]);

        $this->assertTrue(User::find($user->id)->can('gerar_relatorio'));
    }

    public function test_master_profile_receives_no_links(): void
    {
        $this->sync(Profile::administrator(), [$this->permission('gerar_relatorio')->id]);

        $this->assertSame(0, ProfilePermission::withTrashed()->count());
    }
}
