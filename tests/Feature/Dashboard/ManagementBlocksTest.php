<?php

namespace Tests\Feature\Dashboard;

use App\Models\Profile;
use App\Models\User;
use App\Support\Dashboard\Blocks\PendingAccounts;
use App\Support\Dashboard\Blocks\ProfileDistribution;
use App\Support\Dashboard\Blocks\UsersOverview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class ManagementBlocksTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function seedUsers(): void
    {
        User::factory()->count(3)->create();
        User::factory()->inactive()->count(2)->create();
        User::factory()->unverified()->create();
        User::factory()->mustChangePassword()->count(2)->create();
        User::factory()->create(['temporary_password_expires_at' => now()->subHour()]);
        User::factory()->create(['temporary_password_expires_at' => now()->addHour()]);
        User::factory()->count(2)->create()->each->delete();
    }

    /** @param  array<int, array<string, mixed>>  $metrics */
    private function metric(array $metrics, string $key): array
    {
        return Arr::first($metrics, fn (array $metric) => $metric['key'] === $key);
    }

    public function test_the_overview_counts_each_situation(): void
    {
        $this->seedUsers();
        $metrics = app(UsersOverview::class)->data(User::factory()->administrator()->create())['metrics'];

        $this->assertSame(9, $this->metric($metrics, 'active')['value']);
        $this->assertSame(2, $this->metric($metrics, 'inactive')['value']);
        $this->assertSame(1, $this->metric($metrics, 'confirmation_pending')['value']);
        $this->assertSame(2, $this->metric($metrics, 'deleted')['value']);
    }

    public function test_the_deleted_total_has_no_link_without_the_restore_permission(): void
    {
        User::factory()->create()->delete();

        $metrics = app(UsersOverview::class)->data($this->userWith('visualizar_usuario'))['metrics'];

        $this->assertSame(1, $this->metric($metrics, 'deleted')['value']);
        $this->assertNull($this->metric($metrics, 'deleted')['url']);
    }

    public function test_the_deleted_total_has_a_link_with_the_restore_permission(): void
    {
        $metrics = app(UsersOverview::class)->data($this->userWith('visualizar_usuario', 'restaurar_usuario'))['metrics'];

        $this->assertNotNull($this->metric($metrics, 'deleted')['url']);
    }

    public function test_pending_accounts_are_counted_apart(): void
    {
        $this->seedUsers();
        $metrics = app(PendingAccounts::class)->data(User::factory()->create())['metrics'];

        $this->assertSame(2, $this->metric($metrics, 'password_change_pending')['value']);
        $this->assertSame(1, $this->metric($metrics, 'temporary_password_expired')['value']);
    }

    public function test_the_distribution_includes_profiles_without_users_and_ignores_deleted_users(): void
    {
        $empty = Profile::factory()->create(['name' => 'Vazio']);
        $used = Profile::factory()->create(['name' => 'Usado']);
        User::factory()->withProfile($used)->count(2)->create();
        User::factory()->withProfile($used)->create()->delete();

        $metrics = app(ProfileDistribution::class)->data(User::factory()->create())['metrics'];

        $this->assertSame(0, $this->metric($metrics, "profile_{$empty->id}")['value']);
        $this->assertSame(2, $this->metric($metrics, "profile_{$used->id}")['value']);
    }

    /** @return array<string, array{class-string}> */
    public static function blocks(): array
    {
        return [
            'visão geral' => [UsersOverview::class],
            'pendências' => [PendingAccounts::class],
            'distribuição' => [ProfileDistribution::class],
        ];
    }

    #[DataProvider('blocks')]
    public function test_each_link_opens_a_list_with_the_same_quantity(string $block): void
    {
        $this->seedUsers();
        $profile = Profile::factory()->create();
        User::factory()->withProfile($profile)->count(2)->create();

        $actor = User::factory()->administrator()->create();
        $metrics = array_filter(app($block)->data($actor)['metrics'], fn (array $metric) => $metric['url'] !== null);

        $this->assertNotSame([], $metrics);

        foreach ($metrics as $metric) {
            $this->actingAs($actor)->get($metric['url'])->assertOk()
                ->assertInertia(fn (Assert $page) => $page->where('rows.meta.total', $metric['value']));
        }
    }

    public function test_the_list_filters_can_also_show_the_opposite(): void
    {
        User::factory()->mustChangePassword()->create();
        $actor = User::factory()->administrator()->create();

        $this->actingAs($actor)->get(route('admin.users.index', ['filter' => ['password_change_pending' => 0]]))
            ->assertInertia(fn (Assert $page) => $page->where('rows.meta.total', 1));
    }

    public function test_management_data_does_not_reach_who_lacks_the_permission(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertInertia(function (Assert $page) {
                $keys = array_column($page->toArray()['props']['blocks'], 'key');

                $this->assertSame(['ProfileCompletion', 'Notifications', 'MyActivity'], $keys);
            });
    }
}
