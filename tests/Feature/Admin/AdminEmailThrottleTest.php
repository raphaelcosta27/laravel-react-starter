<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class AdminEmailThrottleTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function target(): User
    {
        return User::factory()->withProfile(Profile::factory()->create())->create();
    }

    public function test_sends_are_limited_by_target_user_according_to_the_configuration(): void
    {
        Notification::fake();
        config(['template.admin.email_throttle' => 2]);

        $actor = User::factory()->administrator()->create();
        $target = $this->target();
        $url = "/admin/users/{$target->id}/password-change";

        $this->actingAs($actor)->from('/admin/users')->post($url)->assertSessionHas('flash.type', 'success');
        $this->actingAs($actor)->from('/admin/users')->post($url)->assertSessionHas('flash.type', 'success');

        $this->actingAs($actor)->from('/admin/users')->post($url)
            ->assertRedirect('/admin/users')
            ->assertSessionHas('flash.type', 'warning');

        Notification::assertCount(2);
    }

    public function test_limit_does_not_affect_another_target(): void
    {
        Notification::fake();
        config(['template.admin.email_throttle' => 1]);

        $actor = User::factory()->administrator()->create();
        $first = $this->target();
        $second = $this->target();

        $this->actingAs($actor)->from('/admin/users')->post("/admin/users/{$first->id}/password-change");

        $this->actingAs($actor)->from('/admin/users')
            ->post("/admin/users/{$second->id}/password-change")
            ->assertSessionHas('flash.type', 'success');
    }

    public function test_send_failure_generates_a_warning(): void
    {
        Exceptions::fake();
        Notification::shouldReceive('send')->andThrow(new RuntimeException('Falha no servidor de e-mail'));

        $this->actingAs(User::factory()->administrator()->create())
            ->from('/admin/users')
            ->post('/admin/users/'.$this->target()->id.'/password-change')
            ->assertSessionHas('flash.type', 'warning');
    }

    public function test_non_administrator_cannot_act_on_an_administrator(): void
    {
        Notification::fake();
        $target = User::factory()->administrator()->unverified()->create();
        $user = $this->userWith('solicitar_troca_senha', 'enviar_email_autenticacao');

        $this->actingAs($user)->post("/admin/users/{$target->id}/password-change")->assertForbidden();
        $this->actingAs($user)->post("/admin/users/{$target->id}/authentication-email")->assertForbidden();

        Notification::assertNothingSent();
    }
}
