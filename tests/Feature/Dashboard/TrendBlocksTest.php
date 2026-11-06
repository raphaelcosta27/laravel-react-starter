<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Support\Dashboard\Blocks\Accesses;
use App\Support\Dashboard\Blocks\SystemMovement;
use App\Support\Dashboard\Blocks\UserGrowth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class TrendBlocksTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-06 12:00:00');
    }

    private function history(string $operation, string $date, int $fields = 1): void
    {
        foreach (range(1, $fields) as $field) {
            DB::table('change_history')->insert([
                'operation_id' => $operation,
                'operation' => 'updated',
                'table_name' => 'users',
                'record_id' => '1',
                'field' => "campo{$field}",
                'source' => 'user',
                'created_at' => $date,
            ]);
        }
    }

    /** @return array<int, string> */
    private function keysFor(User $user): array
    {
        $keys = [];

        $this->actingAs($user)->get('/dashboard')->assertInertia(function (Assert $page) use (&$keys) {
            $keys = array_column($page->toArray()['props']['blocks'], 'key');
        });

        return $keys;
    }

    public function test_growth_fills_the_days_without_new_users(): void
    {
        User::factory()->create(['created_at' => '2026-11-06 08:00:00']);
        User::factory()->count(2)->create(['created_at' => '2026-11-04 23:00:00']);
        User::factory()->create(['created_at' => '2026-10-01 08:00:00']);
        config(['template.dashboard.growth_days' => 3]);

        $data = app(UserGrowth::class)->data(User::factory()->make());

        $this->assertSame(3, $data['days']);
        $this->assertSame(3, $data['total']);
        $this->assertSame([
            ['date' => '2026-11-04', 'value' => 2],
            ['date' => '2026-11-05', 'value' => 0],
            ['date' => '2026-11-06', 'value' => 1],
        ], $data['series']);
    }

    public function test_growth_ignores_deleted_accounts(): void
    {
        User::factory()->create(['created_at' => '2026-11-06 08:00:00'])->delete();

        $this->assertSame(0, app(UserGrowth::class)->data(User::factory()->make())['total']);
    }

    public function test_movement_counts_actions_and_not_fields(): void
    {
        config(['template.dashboard.movement_days' => 2]);
        DB::table('change_history')->delete();
        $this->history((string) Str::uuid(), '2026-11-06 09:00:00', 3);
        $this->history((string) Str::uuid(), '2026-11-06 10:00:00');
        $this->history((string) Str::uuid(), '2026-11-01 10:00:00');

        $data = app(SystemMovement::class)->data(User::factory()->make());

        $this->assertSame(2, $data['total']);
        $this->assertSame([
            ['date' => '2026-11-05', 'value' => 0],
            ['date' => '2026-11-06', 'value' => 2],
        ], $data['series']);
    }

    public function test_movement_needs_its_own_permission(): void
    {
        $this->assertNotContains('SystemMovement', $this->keysFor($this->userWith('visualizar_usuario')));
        $this->assertContains('SystemMovement', $this->keysFor($this->userWith('visualizar_historico')));
    }

    public function test_growth_and_accesses_use_the_list_permission(): void
    {
        $keys = $this->keysFor($this->userWith('visualizar_usuario'));

        $this->assertContains('UserGrowth', $keys);
        $this->assertContains('Accesses', $keys);
        $this->assertNotContains('UserGrowth', $this->keysFor(User::factory()->create()));
    }

    public function test_accesses_count_the_period_and_the_accounts_without_record(): void
    {
        config(['template.dashboard.access_days' => 10]);
        User::factory()->create(['last_login_at' => now()->subDays(2)]);
        User::factory()->create(['last_login_at' => now()->subDays(20)]);
        User::factory()->count(2)->create();
        User::factory()->create(['last_login_at' => now()->subDay()])->delete();

        $data = app(Accesses::class)->data(User::factory()->make());

        $this->assertSame(10, $data['days']);
        $this->assertSame(1, $data['active']);
        $this->assertSame(2, $data['without_record']);
    }

    public function test_the_login_records_the_access_without_touching_history_or_updated_at(): void
    {
        $user = User::factory()->create(['updated_at' => '2026-01-01 00:00:00']);
        $history = DB::table('change_history')->count();

        Auth::login($user);

        $fresh = $user->fresh();

        $this->assertSame('2026-11-06 12:00:00', $fresh->last_login_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-01-01', $fresh->updated_at->format('Y-m-d'));
        $this->assertSame($history, DB::table('change_history')->count());
    }

    public function test_the_login_form_records_the_access_when_there_is_no_code_step(): void
    {
        config(['template.login.two_factor_enabled' => false]);
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_the_code_step_does_not_record_the_access(): void
    {
        config(['template.login.two_factor_enabled' => true]);
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('login.code'));

        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_a_wrong_password_does_not_record_the_access(): void
    {
        config(['template.login.two_factor_enabled' => false]);
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'errada']);

        $this->assertNull($user->fresh()->last_login_at);
    }
}
