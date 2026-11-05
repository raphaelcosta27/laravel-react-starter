<?php

namespace Tests\Feature\History;

use App\Actions\CreateUser;
use App\Models\ChangeHistory;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsibleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_change_in_the_panel_points_to_the_logged_user(): void
    {
        $administrator = User::factory()->administrator()->create();
        $profile = Profile::factory()->create();

        $this->actingAs($administrator)->patch("/admin/profiles/{$profile->id}", ['name' => 'Novo']);

        $item = ChangeHistory::of($profile)->where('operation', 'updated')->where('field', 'name')->sole();

        $this->assertSame($administrator->id, $item->user_id);
        $this->assertSame('user', $item->source);
        $this->assertNotNull($item->created_at);
    }

    public function test_a_change_without_a_logged_user_comes_from_the_system(): void
    {
        $profile = Profile::factory()->create();

        $item = ChangeHistory::of($profile)->first();

        $this->assertNull($item->user_id);
        $this->assertSame('system', $item->source);
    }

    public function test_a_public_registration_comes_from_the_system(): void
    {
        $user = app(CreateUser::class)->handle('Ana', 'ana@example.com');

        $this->assertSame('system', ChangeHistory::of($user)->first()->source);
    }

    public function test_the_author_stays_identifiable_after_being_deleted(): void
    {
        $administrator = User::factory()->administrator()->create();
        $profile = Profile::factory()->create();

        $this->actingAs($administrator);
        $profile->update(['name' => 'Novo']);

        User::whereKey($administrator->id)->first()->delete();

        $item = ChangeHistory::of($profile)->where('operation', 'updated')->sole();

        $this->assertTrue($item->actor->is($administrator));
    }
}
