<?php

namespace Tests\Feature;

use App\Models\ChangeHistory;
use App\Models\Profile;
use App\Models\ScreenNotification;
use App\Models\User;
use App\Support\ScreenNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScreenNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_call_sends_to_a_person(): void
    {
        $user = User::factory()->create();

        ScreenNotifications::send($user, 'Título', 'Texto', 'success', '/dashboard');

        $this->assertDatabaseHas('screen_notifications', [
            'user_id' => $user->id,
            'title' => 'Título',
            'body' => 'Texto',
            'type' => 'success',
            'link' => '/dashboard',
            'seen_at' => null,
        ]);
    }

    public function test_one_call_sends_to_everyone_of_a_profile(): void
    {
        $profile = Profile::factory()->create();
        $users = User::factory()->count(3)->withProfile($profile)->create();

        ScreenNotifications::send($profile->users, 'Título', 'Texto');

        foreach ($users as $user) {
            $this->assertSame(1, ScreenNotifications::unread($user));
        }
    }

    public function test_an_unknown_type_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ScreenNotifications::send(User::factory()->create(), 'Título', 'Texto', 'fatal');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeLinks(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'data' => ['data:text/html,x'],
            'outra origem sem protocolo' => ['//example.com'],
            'barra invertida' => ['/\\example.com'],
            'sem barra' => ['dashboard'],
        ];
    }

    #[DataProvider('unsafeLinks')]
    public function test_an_unsafe_link_is_refused(string $link): void
    {
        $this->expectException(InvalidArgumentException::class);

        ScreenNotifications::send(User::factory()->create(), 'Título', 'Texto', 'info', $link);
    }

    public function test_an_external_link_is_accepted(): void
    {
        $user = User::factory()->create();

        ScreenNotifications::send($user, 'Título', 'Texto', 'info', 'https://example.com/a');

        $this->assertSame(1, ScreenNotifications::unread($user));
    }

    public function test_every_page_shares_the_count(): void
    {
        $user = User::factory()->create();
        ScreenNotification::factory()->for($user)->count(2)->create();
        ScreenNotification::factory()->for($user)->seen()->create();
        ScreenNotification::factory()->for(User::factory()->create())->create();

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('notifications.unread', 2)
            ->where('notifications.pollSeconds', 60));
    }

    public function test_visitors_share_nothing(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('notifications', null));
    }

    public function test_the_list_shows_the_latest_first_and_tells_what_is_new(): void
    {
        $user = User::factory()->create();
        $old = ScreenNotification::factory()->for($user)->seen()->create();
        $new = ScreenNotification::factory()->for($user)->create(['link' => '/dashboard']);

        $this->actingAs($user)->getJson('/notifications')->assertOk()
            ->assertJsonPath('data.0.id', $new->id)
            ->assertJsonPath('data.0.is_new', true)
            ->assertJsonPath('data.0.link', '/dashboard')
            ->assertJsonPath('data.1.id', $old->id)
            ->assertJsonPath('data.1.is_new', false)
            ->assertJsonPath('next_before', null)
            ->assertJsonPath('unread', 1);
    }

    public function test_the_list_is_paginated(): void
    {
        config(['template.notifications.per_page' => 2]);
        $user = User::factory()->create();
        $notifications = ScreenNotification::factory()->for($user)->count(3)->create();

        $this->actingAs($user)->getJson('/notifications')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('next_before', $notifications[1]->id);

        $this->getJson("/notifications?before={$notifications[1]->id}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $notifications[0]->id)
            ->assertJsonPath('next_before', null);
    }

    public function test_hiding_one_does_not_skip_the_next_page(): void
    {
        config(['template.notifications.per_page' => 2]);
        $user = User::factory()->create();
        $notifications = ScreenNotification::factory()->for($user)->count(4)->create();

        $next = $this->actingAs($user)->getJson('/notifications')->json('next_before');

        $this->deleteJson("/notifications/{$notifications[3]->id}");

        $this->getJson("/notifications?before={$next}")
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $notifications[1]->id);
    }

    public function test_opening_the_list_alone_changes_nothing(): void
    {
        $user = User::factory()->create();
        ScreenNotification::factory()->for($user)->create();

        $this->actingAs($user)->getJson('/notifications');

        $this->assertSame(1, ScreenNotifications::unread($user));
    }

    public function test_the_count_can_be_polled(): void
    {
        $user = User::factory()->create();
        ScreenNotification::factory()->for($user)->count(2)->create();

        $this->actingAs($user)->getJson('/notifications/unread')->assertExactJson(['unread' => 2]);
    }

    public function test_shown_notifications_stop_counting_and_stay_listed(): void
    {
        $user = User::factory()->create();
        $shown = ScreenNotification::factory()->for($user)->create();
        ScreenNotification::factory()->for($user)->create();

        $this->actingAs($user)->postJson('/notifications/seen', ['ids' => [$shown->id]])
            ->assertExactJson(['unread' => 1]);

        $this->assertNotNull($shown->refresh()->seen_at);

        $this->getJson('/notifications')->assertJsonCount(2, 'data');
    }

    public function test_seeing_again_keeps_the_first_date(): void
    {
        $user = User::factory()->create();
        $seen = ScreenNotification::factory()->for($user)->create(['seen_at' => now()->subDay()]);

        $this->actingAs($user)->postJson('/notifications/seen', ['ids' => [$seen->id]]);

        $this->assertTrue($seen->refresh()->seen_at->lt(now()->subHour()));
    }

    public function test_notifications_of_another_person_are_not_marked(): void
    {
        $foreign = ScreenNotification::factory()->for(User::factory()->create())->create();

        $this->actingAs(User::factory()->create())->postJson('/notifications/seen', ['ids' => [$foreign->id]]);

        $this->assertNull($foreign->refresh()->seen_at);
    }

    public function test_an_invalid_seen_request_is_refused(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/notifications/seen', ['ids' => 'x'])->assertUnprocessable();
        $this->postJson('/notifications/seen', ['ids' => ['x']])->assertUnprocessable();
        $this->postJson('/notifications/seen', ['ids' => range(1, 101)])->assertUnprocessable();
    }

    public function test_hiding_keeps_the_record_and_updates_the_count(): void
    {
        $user = User::factory()->create();
        $notification = ScreenNotification::factory()->for($user)->create();

        $this->actingAs($user)->deleteJson("/notifications/{$notification->id}")
            ->assertExactJson(['unread' => 0]);

        $this->assertSoftDeleted($notification);

        $this->getJson('/notifications')->assertJsonCount(0, 'data');
    }

    public function test_a_notification_of_another_person_cannot_be_hidden(): void
    {
        $foreign = ScreenNotification::factory()->for(User::factory()->create())->create();

        $this->actingAs(User::factory()->administrator()->create())
            ->deleteJson("/notifications/{$foreign->id}")
            ->assertNotFound();

        $this->assertNotSoftDeleted($foreign);
    }

    public function test_the_list_shows_only_the_own_notifications(): void
    {
        ScreenNotification::factory()->for(User::factory()->create())->create();

        $this->actingAs(User::factory()->administrator()->create())->getJson('/notifications')
            ->assertJsonCount(0, 'data');
    }

    public function test_an_expired_session_is_told_so(): void
    {
        $this->getJson('/notifications/unread')->assertUnauthorized();
        $this->deleteJson('/notifications/1')->assertUnauthorized();
    }

    public function test_a_deleted_account_keeps_its_notifications(): void
    {
        $user = User::factory()->create();
        $notification = ScreenNotification::factory()->for($user)->create();

        $user->delete();

        $this->assertNotSoftDeleted($notification);
    }

    public function test_it_can_be_turned_off(): void
    {
        config(['template.notifications.enabled' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('notifications', null));

        $this->getJson('/notifications')->assertNotFound();
        $this->getJson('/notifications/unread')->assertNotFound();
    }

    public function test_the_history_records_the_notification(): void
    {
        $user = User::factory()->create();

        ScreenNotifications::send($user, 'Título', 'Texto');

        $this->assertTrue(ChangeHistory::where('table_name', 'screen_notifications')->exists());
    }
}
