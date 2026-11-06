<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\WelcomeUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationToggleTest extends TestCase
{
    use RefreshDatabase;

    private array $data = [
        'name' => 'Ana',
        'email' => 'ana@example.com',
        'password' => 'Senha-escolhida-1',
        'password_confirmation' => 'Senha-escolhida-1',
    ];

    public function test_registration_is_unavailable_by_default(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', $this->data)->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_enabled_registration_keeps_the_account_rules(): void
    {
        Notification::fake();
        config(['template.registration_enabled' => true]);

        $this->post('/register', $this->data);

        $user = User::firstWhere('email', 'ana@example.com');

        $this->assertTrue($user->must_change_password);
        $this->assertNull($user->temporary_password_expires_at);

        Notification::assertSentToTimes($user, WelcomeUser::class, 1);
        Notification::assertSentTo($user, WelcomeUser::class, fn ($notification) => $notification->temporaryPassword === null);
    }

    public function test_can_register_follows_the_configuration(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('canRegister', false)->etc());
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('canRegister', false)->etc());

        config(['template.registration_enabled' => true]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('canRegister', true)->etc());
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('canRegister', true)->etc());
    }
}
