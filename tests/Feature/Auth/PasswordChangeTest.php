<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private function change(User $user, array $data)
    {
        return $this->actingAs($user)->from('/password/change')->put('/password', $data);
    }

    public function test_valid_change_releases_the_account(): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $this->change($user, [
            'current_password' => 'password',
            'password' => 'Nova-senha-1',
            'password_confirmation' => 'Nova-senha-1',
        ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('Nova-senha-1', $user->password));
    }

    #[DataProvider('invalidData')]
    public function test_invalid_change_keeps_the_flag(array $data, string $field): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $this->change($user, $data)->assertSessionHasErrors($field);

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public static function invalidData(): array
    {
        return [
            'senha atual errada' => [[
                'current_password' => 'errada',
                'password' => 'Nova-senha-1',
                'password_confirmation' => 'Nova-senha-1',
            ], 'current_password'],
            'sem confirmação' => [[
                'current_password' => 'password',
                'password' => 'Nova-senha-1',
                'password_confirmation' => 'diferente',
            ], 'password'],
            'igual à atual' => [[
                'current_password' => 'password',
                'password' => 'password',
                'password_confirmation' => 'password',
            ], 'password'],
            'menos de 8 caracteres' => [[
                'current_password' => 'password',
                'password' => 'curta',
                'password_confirmation' => 'curta',
            ], 'password'],
        ];
    }

    public function test_change_ends_other_sessions_and_keeps_the_current_one(): void
    {
        $user = User::factory()->mustChangePassword()->create();
        $otherSession = Auth::guard('web')->hashPasswordForCookie($user->password);

        $this->change($user, [
            'current_password' => 'password',
            'password' => 'Nova-senha-1',
            'password_confirmation' => 'Nova-senha-1',
        ]);

        $this->get('/dashboard')->assertOk();

        $this->flushSession();
        Auth::forgetGuards();
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['password_hash_web' => $otherSession])
            ->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_profile_change_stays_on_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password',
            'password' => 'Nova-senha-1',
            'password_confirmation' => 'Nova-senha-1',
        ])->assertRedirect('/profile');
    }
}
