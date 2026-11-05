<?php

namespace Tests\Feature\History;

use App\Models\ChangeHistory;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MassOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_mass_update_records_each_affected_row(): void
    {
        $first = Profile::factory()->create(['description' => 'Velha']);
        $second = Profile::factory()->create(['description' => 'Velha']);
        $other = Profile::factory()->create(['description' => 'Outra']);

        $count = Profile::where('description', 'Velha')->update(['description' => 'Nova']);

        $this->assertSame(2, $count);
        $this->assertSame('Nova', $first->fresh()->description);

        foreach ([$first, $second] as $profile) {
            $item = ChangeHistory::of($profile)->where('operation', 'updated')->sole();

            $this->assertSame('Velha', $item->old_value);
            $this->assertSame('Nova', $item->new_value);
        }

        $this->assertCount(0, ChangeHistory::of($other)->where('operation', 'updated')->get());
    }

    public function test_a_mass_update_respects_deleted_records(): void
    {
        $profile = Profile::factory()->create();
        $profile->delete();

        $this->assertSame(0, Profile::whereKey($profile->id)->update(['description' => 'Nova']));
        $this->assertSame(1, Profile::withTrashed()->whereKey($profile->id)->update(['description' => 'Nova']));
    }

    public function test_a_mass_update_through_a_relation_is_recorded(): void
    {
        $profile = Profile::factory()->create();
        $user = User::factory()->withProfile($profile)->create();

        $profile->users()->update(['name' => 'Novo']);

        $this->assertSame('Novo', ChangeHistory::of($user)->where('operation', 'updated')->sole()->new_value);
    }

    public function test_a_mass_update_with_an_expression_does_not_fail(): void
    {
        $profile = Profile::factory()->create();

        Profile::whereKey($profile->id)->update(['description' => DB::raw("'Nova'")]);

        $this->assertSame('Nova', $profile->fresh()->description);
        $this->assertCount(1, ChangeHistory::of($profile)->where('operation', 'updated')->get());
    }

    public function test_a_mass_delete_records_each_row_as_deleted(): void
    {
        $profiles = Profile::factory()->count(2)->create();

        $count = Profile::whereKey($profiles->modelKeys())->delete();

        $this->assertSame(2, $count);

        foreach ($profiles as $profile) {
            $this->assertTrue($profile->fresh()->trashed());
            $this->assertSame('deleted_at', ChangeHistory::of($profile)->where('operation', 'deleted')->sole()->field);
        }
    }

    public function test_a_mass_restore_records_each_row_as_restored(): void
    {
        $profiles = Profile::factory()->count(2)->create();

        Profile::whereKey($profiles->modelKeys())->delete();

        $count = Profile::onlyTrashed()->whereKey($profiles->modelKeys())->restore();

        $this->assertSame(2, $count);

        foreach ($profiles as $profile) {
            $this->assertFalse($profile->fresh()->trashed());
            $this->assertCount(1, ChangeHistory::of($profile)->where('operation', 'restored')->get());
        }
    }
}
