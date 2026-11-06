<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Support\Dashboard\Block;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class DashboardBlocksTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FakeBlock::$calculated = 0;
    }

    /** @param  array<int, class-string<Block>>  $blocks */
    private function blocks(array $blocks): void
    {
        config(['template.dashboard.blocks' => $blocks, 'template.dashboard.disabled' => []]);
    }

    /** @return array<int, string> */
    private function keysFor(User $user): array
    {
        $keys = [];

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertInertia(function (Assert $page) use (&$keys) {
            $page->component('Dashboard');
            $keys = array_column($page->toArray()['props']['blocks'], 'key');
        });

        return $keys;
    }

    public function test_a_block_without_permission_reaches_everyone(): void
    {
        $this->blocks([FakeBlock::class]);

        $this->assertSame(['FakeBlock'], $this->keysFor(User::factory()->create()));
    }

    public function test_a_restricted_block_is_neither_calculated_nor_sent_without_the_permission(): void
    {
        $this->blocks([RestrictedBlock::class]);

        $this->assertSame([], $this->keysFor(User::factory()->create()));
        $this->assertSame(0, FakeBlock::$calculated);
    }

    public function test_a_restricted_block_reaches_who_has_the_permission(): void
    {
        $this->blocks([RestrictedBlock::class]);

        $this->assertSame(['RestrictedBlock'], $this->keysFor($this->userWith('visualizar_usuario')));
    }

    public function test_the_administrator_sees_every_block(): void
    {
        $this->blocks([FakeBlock::class, RestrictedBlock::class]);

        $this->assertSame(['FakeBlock', 'RestrictedBlock'], $this->keysFor(User::factory()->administrator()->create()));
    }

    public function test_the_order_follows_the_configuration(): void
    {
        $this->blocks([RestrictedBlock::class, FakeBlock::class]);

        $this->assertSame(['RestrictedBlock', 'FakeBlock'], $this->keysFor(User::factory()->administrator()->create()));
    }

    public function test_an_unavailable_block_is_not_calculated(): void
    {
        $this->blocks([UnavailableBlock::class]);

        $this->assertSame([], $this->keysFor(User::factory()->create()));
        $this->assertSame(0, FakeBlock::$calculated);
    }

    public function test_a_disabled_block_is_not_calculated(): void
    {
        $this->blocks([FakeBlock::class]);
        config(['template.dashboard.disabled' => ['FakeBlock']]);

        $this->assertSame([], $this->keysFor(User::factory()->create()));
        $this->assertSame(0, FakeBlock::$calculated);
    }

    public function test_a_block_failure_does_not_stop_the_others_or_show_the_error(): void
    {
        Log::spy();
        $this->blocks([BrokenBlock::class, FakeBlock::class]);

        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()
            ->assertDontSee('segredo interno')
            ->assertInertia(fn (Assert $page) => $page
                ->where('blocks.0.key', 'BrokenBlock')
                ->where('blocks.0.failed', true)
                ->where('blocks.0.data', null)
                ->where('blocks.1.key', 'FakeBlock')
                ->where('blocks.1.failed', false)
                ->where('blocks.1.data.value', 1));

        Log::shouldHaveReceived('error')->once();
    }

    public function test_a_guest_goes_to_the_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}

class FakeBlock implements Block
{
    public static int $calculated = 0;

    public function key(): string
    {
        return class_basename(static::class);
    }

    public function title(): string
    {
        return 'Bloco';
    }

    public function permission(): ?string
    {
        return null;
    }

    public function available(): bool
    {
        return true;
    }

    public function data(User $user): array
    {
        self::$calculated++;

        return ['value' => 1];
    }
}

class RestrictedBlock extends FakeBlock
{
    public function permission(): ?string
    {
        return 'visualizar_usuario';
    }
}

class UnavailableBlock extends FakeBlock
{
    public function available(): bool
    {
        return false;
    }
}

class BrokenBlock extends FakeBlock
{
    public function data(User $user): array
    {
        throw new RuntimeException('segredo interno');
    }
}
