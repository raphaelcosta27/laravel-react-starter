<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\ProfilePermission;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class EffectivePermissionsTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function adjust(User $user, string $code, string $effect): UserPermission
    {
        return UserPermission::create([
            'user_id' => $user->id,
            'permission_id' => $this->permission($code)->id,
            'effect' => $effect,
        ]);
    }

    public function test_profile_permission_is_effective(): void
    {
        $this->assertTrue($this->userWith('gerar_relatorio')->can('gerar_relatorio'));
    }

    public function test_user_without_the_permission_is_denied(): void
    {
        $this->assertFalse($this->userWith()->can('gerar_relatorio'));
    }

    public function test_individual_grant_is_effective(): void
    {
        $user = $this->userWith();
        $this->adjust($user, 'gerar_relatorio', 'grant');

        $this->assertTrue($user->can('gerar_relatorio'));
    }

    public function test_revocation_wins_over_the_profile(): void
    {
        $user = $this->userWith('gerar_relatorio');
        $this->adjust($user, 'gerar_relatorio', 'revoke');

        $this->assertFalse($user->can('gerar_relatorio'));
    }

    public function test_deleted_link_has_no_effect(): void
    {
        $user = $this->userWith('gerar_relatorio');
        ProfilePermission::where('profile_id', $user->profile_id)->first()->delete();

        $this->assertFalse($user->can('gerar_relatorio'));
    }

    public function test_deleted_individual_adjustment_has_no_effect(): void
    {
        $user = $this->userWith();
        $this->adjust($user, 'gerar_relatorio', 'grant')->delete();

        $this->assertFalse($user->can('gerar_relatorio'));
    }

    public function test_deleted_permission_has_no_effect(): void
    {
        $user = $this->userWith('gerar_relatorio');
        $this->permission('gerar_relatorio')->delete();

        $this->assertFalse($user->can('gerar_relatorio'));
    }

    public function test_deleted_profile_has_no_effect(): void
    {
        $user = $this->userWith('gerar_relatorio');
        Profile::find($user->profile_id)->delete();

        $this->assertFalse(User::find($user->id)->can('gerar_relatorio'));
    }

    public function test_administrator_passes_any_code_without_reading_permissions(): void
    {
        $user = User::factory()->administrator()->create();
        $user = User::with('profile')->find($user->id);
        $queries = [];

        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->assertTrue($user->can('codigo_que_nao_existe'));
        $this->assertSame([], array_filter($queries, fn (string $sql) => str_contains($sql, 'permission')));
    }

    public function test_administrator_cannot_be_revoked(): void
    {
        $user = User::factory()->administrator()->create();
        $this->adjust($user, 'gerar_relatorio', 'revoke');

        $this->assertTrue($user->can('gerar_relatorio'));
    }

    public function test_change_is_valid_on_the_next_request(): void
    {
        Route::middleware(['web', 'can:gerar_relatorio'])->get('/_protegida', fn () => 'ok');

        $user = $this->userWith();

        $this->nextRequestAs($user)->get('/_protegida')->assertForbidden();

        $this->adjust($user, 'gerar_relatorio', 'grant');

        $this->nextRequestAs($user)->get('/_protegida')->assertOk();
    }
}
