<?php

namespace Tests\Feature\History;

use App\Models\ChangeHistory;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ImmutableHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function item(): ChangeHistory
    {
        return ChangeHistory::of(Profile::factory()->create())->first();
    }

    public function test_an_item_cannot_be_changed(): void
    {
        $item = $this->item();

        $this->expectException(LogicException::class);

        $item->forceFill(['new_value' => 'x'])->save();
    }

    public function test_an_item_cannot_be_created_through_the_model(): void
    {
        $this->expectException(LogicException::class);

        (new ChangeHistory)->save();
    }

    public function test_an_item_cannot_be_deleted(): void
    {
        $item = $this->item();

        $this->expectException(LogicException::class);

        $item->delete();
    }

    public function test_items_cannot_be_changed_in_mass(): void
    {
        $this->item();

        $this->expectException(LogicException::class);

        ChangeHistory::query()->update(['new_value' => 'x']);
    }

    public function test_items_cannot_be_deleted_in_mass(): void
    {
        $this->item();

        $this->expectException(LogicException::class);

        ChangeHistory::query()->delete();
    }
}
