<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPasswordFlagTest extends TestCase
{
    use RefreshDatabase;

    private function attributes(array $extra = []): array
    {
        return [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'senha-inicial',
            ...$extra,
        ];
    }

    public function test_new_users_must_change_password(): void
    {
        $user = User::create($this->attributes());

        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_flag_cannot_be_mass_assigned(): void
    {
        $user = User::create($this->attributes(['must_change_password' => false]));

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_flag_can_be_reactivated(): void
    {
        $user = User::factory()->create();

        $user->requirePasswordChange();

        $this->assertTrue($user->fresh()->must_change_password);
    }
}
