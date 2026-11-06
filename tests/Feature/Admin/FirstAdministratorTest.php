<?php

namespace Tests\Feature\Admin;

use App\Actions\CreateUser;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FirstAdministratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function createUser(): CreateUser
    {
        return app(CreateUser::class);
    }

    public function test_first_user_created_by_handle_becomes_administrator(): void
    {
        $user = $this->createUser()->handle('Ana', 'ana@example.com');

        $this->assertTrue($user->fresh()->isAdministrator());
    }

    public function test_first_user_created_by_verified_becomes_administrator(): void
    {
        $user = $this->createUser()->verified('Ana', 'ana@example.com', 'Senha-segura-1');

        $this->assertTrue($user->fresh()->isAdministrator());
    }

    public function test_first_user_ignores_the_informed_profile(): void
    {
        $profile = Profile::factory()->create();

        $user = $this->createUser()->handle('Ana', 'ana@example.com', profile: $profile);

        $this->assertTrue($user->fresh()->isAdministrator());
    }

    public function test_second_user_gets_the_informed_profile(): void
    {
        $profile = Profile::factory()->create();
        $this->createUser()->handle('Ana', 'ana@example.com');

        $user = $this->createUser()->handle('Bia', 'bia@example.com', profile: $profile);

        $this->assertSame($profile->id, $user->fresh()->profile_id);
    }

    public function test_second_user_created_in_sequence_does_not_get_administrator(): void
    {
        $this->createUser()->handle('Ana', 'ana@example.com');

        $user = $this->createUser()->handle('Bia', 'bia@example.com');

        $this->assertFalse($user->fresh()->isAdministrator());
        $this->assertSame(Profile::default()->id, $user->fresh()->profile_id);
    }

    public function test_user_created_after_a_deleted_one_is_not_the_first(): void
    {
        User::factory()->create()->delete();

        $user = $this->createUser()->handle('Bia', 'bia@example.com');

        $this->assertFalse($user->fresh()->isAdministrator());
    }

    public function test_public_registration_uses_the_same_rule(): void
    {
        config(['template.registration_enabled' => true]);

        $this->post('/register', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'Senha-segura-1',
            'password_confirmation' => 'Senha-segura-1',
        ]);

        $this->assertTrue(User::firstWhere('email', 'ana@example.com')->isAdministrator());
    }
}
