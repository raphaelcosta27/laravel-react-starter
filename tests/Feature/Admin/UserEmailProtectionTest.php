<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class UserEmailProtectionTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_a_non_administrator_cannot_change_another_users_email(): void
    {
        $target = $this->userWith();
        $email = $target->email;

        $this->actingAs($this->userWith('editar_usuario'))
            ->patch("/admin/users/{$target->id}", ['email' => 'novo@example.com'])
            ->assertForbidden();

        $this->assertSame($email, $target->fresh()->email);
        Notification::assertNothingSent();
    }

    public function test_a_non_administrator_cannot_change_their_email_through_the_panel(): void
    {
        $actor = $this->userWith('editar_usuario');
        $email = $actor->email;

        $this->actingAs($actor)
            ->patch("/admin/users/{$actor->id}", ['email' => 'novo@example.com'])
            ->assertForbidden();

        $this->assertSame($email, $actor->fresh()->email);
    }

    public function test_a_refused_email_change_saves_nothing_else(): void
    {
        $target = $this->userWith();
        $name = $target->name;

        $this->actingAs($this->userWith('editar_usuario'))
            ->patch("/admin/users/{$target->id}", ['name' => 'Outro', 'email' => 'novo@example.com'])
            ->assertForbidden();

        $this->assertSame($name, $target->fresh()->name);
    }

    public function test_a_non_administrator_edits_the_other_fields_with_the_same_email(): void
    {
        $target = $this->userWith();

        $this->actingAs($this->userWith('editar_usuario'))
            ->patch("/admin/users/{$target->id}", ['name' => 'Outro', 'email' => $target->email])
            ->assertSessionHasNoErrors();

        $this->assertSame('Outro', $target->fresh()->name);
    }

    public function test_an_administrator_changes_the_email(): void
    {
        $target = $this->userWith();

        $this->actingAs(User::factory()->administrator()->create())
            ->patch("/admin/users/{$target->id}", ['email' => 'novo@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertSame('novo@example.com', $target->fresh()->email);
    }

    public function test_the_form_tells_who_can_change_the_email(): void
    {
        $target = $this->userWith();

        $this->actingAs($this->userWith('visualizar_usuario', 'editar_usuario'))
            ->get("/admin/users/{$target->id}/edit")
            ->assertInertia(fn (Assert $page) => $page->where('form.canChangeEmail', false));

        $this->actingAs(User::factory()->administrator()->create())
            ->get("/admin/users/{$target->id}/edit")
            ->assertInertia(fn (Assert $page) => $page->where('form.canChangeEmail', true));
    }

    public function test_the_creation_form_keeps_the_email_open(): void
    {
        $this->actingAs($this->userWith('visualizar_usuario', 'cadastrar_usuario'))
            ->get('/admin/users/create')
            ->assertInertia(fn (Assert $page) => $page->where('form.canChangeEmail', true));
    }

    public function test_a_user_still_changes_their_own_email_in_their_account(): void
    {
        $user = $this->userWith();

        $this->actingAs($user)
            ->patch('/profile', ['name' => $user->name, 'email' => 'minha@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertSame('minha@example.com', $user->fresh()->email);
    }
}
