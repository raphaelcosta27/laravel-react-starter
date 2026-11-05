<?php

namespace Tests\Feature\History;

use App\Models\ChangeHistory;
use App\Models\Profile;
use App\Models\ProfilePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class RecordsChangesTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function items(Model $model, string $operation)
    {
        return ChangeHistory::of($model)->where('operation', $operation)->get()->keyBy('field');
    }

    public function test_creation_records_the_initial_values(): void
    {
        $profile = Profile::factory()->create(['name' => 'Financeiro', 'description' => 'Equipe']);

        $items = $this->items($profile, 'created');

        $this->assertNull($items['name']->old_value);
        $this->assertSame('Financeiro', $items['name']->new_value);
        $this->assertSame('Equipe', $items['description']->new_value);
        $this->assertSame('profiles', $items['name']->table_name);
        $this->assertSame((string) $profile->id, $items['name']->record_id);
    }

    public function test_key_and_automatic_dates_are_left_out(): void
    {
        $profile = Profile::factory()->create();

        $this->assertEmpty($this->items($profile, 'created')->only(['id', 'created_at', 'updated_at']));
    }

    public function test_each_changed_field_has_its_own_item_in_the_same_operation(): void
    {
        $profile = Profile::factory()->create(['name' => 'Antigo', 'description' => 'Velha']);

        $profile->update(['name' => 'Novo', 'description' => 'Nova']);

        $items = $this->items($profile, 'updated');

        $this->assertCount(2, $items);
        $this->assertSame('Antigo', $items['name']->old_value);
        $this->assertSame('Novo', $items['name']->new_value);
        $this->assertSame('Velha', $items['description']->old_value);
        $this->assertSame($items['name']->operation_id, $items['description']->operation_id);
    }

    public function test_two_operations_have_different_identifiers(): void
    {
        $profile = Profile::factory()->create();

        $profile->update(['name' => 'Um']);
        $first = ChangeHistory::of($profile)->latest('id')->first();

        $profile->update(['name' => 'Dois']);

        $this->assertNotSame($first->operation_id, ChangeHistory::of($profile)->latest('id')->first()->operation_id);
    }

    public function test_a_save_without_real_change_records_nothing(): void
    {
        $profile = Profile::factory()->create(['name' => 'Mesmo']);

        $profile->update(['name' => 'Mesmo']);
        $profile->save();

        $this->assertCount(0, $this->items($profile, 'updated'));
    }

    public function test_automatic_update_date_has_no_item(): void
    {
        $profile = Profile::factory()->create();

        $this->travel(1)->minutes();
        $profile->touch();

        $this->assertCount(0, $this->items($profile, 'updated'));
    }

    public function test_soft_delete_and_restore_are_recorded(): void
    {
        $profile = Profile::factory()->create();

        $profile->delete();

        $deleted = $this->items($profile, 'deleted');

        $this->assertCount(1, $deleted);
        $this->assertNull($deleted['deleted_at']->old_value);
        $this->assertNotNull($deleted['deleted_at']->new_value);

        $profile->restore();

        $restored = $this->items($profile, 'restored');

        $this->assertCount(1, $restored);
        $this->assertSame($deleted['deleted_at']->new_value, $restored['deleted_at']->old_value);
        $this->assertNull($restored['deleted_at']->new_value);
        $this->assertCount(0, $this->items($profile, 'updated'));
    }

    public function test_a_physical_delete_records_the_last_values(): void
    {
        $profile = Profile::factory()->create(['name' => 'Último']);

        $profile->forceDelete();

        $items = $this->items($profile, 'deleted');

        $this->assertSame('Último', $items['name']->old_value);
        $this->assertNull($items['name']->new_value);
    }

    public function test_links_are_recorded(): void
    {
        $link = ProfilePermission::create([
            'profile_id' => Profile::factory()->create()->id,
            'permission_id' => $this->permission('gerar_relatorio')->id,
        ]);

        $link->delete();

        $this->assertSame('profile_permission', $this->items($link, 'created')['profile_id']->table_name);
        $this->assertCount(1, $this->items($link, 'deleted'));
    }

    public function test_new_columns_are_recorded_without_configuration(): void
    {
        Schema::table('profiles', fn ($table) => $table->string('apelido')->nullable());

        $profile = Profile::factory()->create();

        $profile->forceFill(['apelido' => 'Fin'])->save();

        $this->assertSame('Fin', $this->items($profile, 'updated')['apelido']->new_value);
    }

    public function test_a_transaction_rolled_back_leaves_no_history(): void
    {
        $profile = Profile::factory()->create(['name' => 'Antes']);

        try {
            DB::transaction(function () use ($profile) {
                $profile->update(['name' => 'Depois']);

                throw new RuntimeException('falha');
            });
        } catch (RuntimeException) {
            //
        }

        $this->assertCount(0, $this->items($profile, 'updated'));
        $this->assertSame('Antes', $profile->fresh()->name);
    }

    public function test_a_failure_in_the_history_undoes_the_change(): void
    {
        $profile = Profile::factory()->create(['name' => 'Antes']);

        Schema::drop('change_history');

        try {
            $profile->update(['name' => 'Depois']);

            $this->fail('A falha no histórico deveria interromper a alteração.');
        } catch (QueryException) {
            $this->assertSame('Antes', $profile->fresh()->name);
        }
    }

    public function test_long_values_are_cut(): void
    {
        $profile = Profile::factory()->create();

        $profile->update(['description' => str_repeat('a', 20000)]);

        $this->assertSame(10000, mb_strlen($this->items($profile, 'updated')['description']->new_value));
    }

    public function test_binary_values_have_a_representation(): void
    {
        $profile = Profile::factory()->create();

        $profile->update(['description' => "\xFF\xFE"]);

        $this->assertSame('[binary]', $this->items($profile, 'updated')['description']->new_value);
    }

    public function test_booleans_are_stored_as_one_and_zero(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create(['is_active' => true]);

        $user->forceFill(['is_active' => false])->save();

        $item = $this->items($user, 'updated')['is_active'];

        $this->assertSame('1', $item->old_value);
        $this->assertSame('0', $item->new_value);
    }
}
