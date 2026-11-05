<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use App\Notifications\WelcomeUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendAuthenticationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function send(User $target)
    {
        return $this->actingAs(User::factory()->administrator()->create())
            ->from('/admin/users')
            ->post("/admin/users/{$target->id}/authentication-email");
    }

    private function unconfirmed(array $attributes = []): User
    {
        return User::factory()
            ->withProfile(Profile::factory()->create())
            ->unverified()
            ->create($attributes);
    }

    public function test_issues_a_new_password_and_link_even_with_a_valid_previous_one(): void
    {
        $target = $this->unconfirmed(['temporary_password_expires_at' => now()->addHour()]);

        $this->send($target)->assertSessionHas('flash.type', 'success');

        $this->assertFalse(Hash::check('password', $target->fresh()->password));

        Notification::assertSentTo($target, WelcomeUser::class, fn (WelcomeUser $notification) => $notification->temporaryPassword !== null);
    }

    public function test_activates_the_required_change_even_after_a_previous_change(): void
    {
        $target = $this->unconfirmed(['must_change_password' => false]);

        $this->send($target);

        $this->assertTrue($target->fresh()->must_change_password);
    }

    public function test_confirmed_account_is_refused(): void
    {
        $target = User::factory()->withProfile(Profile::factory()->create())->create();

        $this->send($target)->assertSessionHasErrors('user');

        Notification::assertNothingSent();
    }

    public function test_inactive_account_is_refused(): void
    {
        $this->send($this->unconfirmed(['is_active' => false]))->assertSessionHasErrors('user');

        Notification::assertNothingSent();
    }

    public function test_route_requires_the_permission(): void
    {
        $this->actingAs(User::factory()->withProfile(Profile::factory()->create())->create())
            ->post('/admin/users/'.$this->unconfirmed()->id.'/authentication-email')
            ->assertForbidden();
    }
}
