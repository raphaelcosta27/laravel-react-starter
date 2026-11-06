<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class AppCreateUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function command(): PendingCommand
    {
        return $this->artisan('app:create-user');
    }

    private function answer(PendingCommand $command, string $question, string $answer): PendingCommand
    {
        return $command->expectsQuestion(__($question), $answer);
    }

    private function answerValidData(PendingCommand $command): PendingCommand
    {
        $this->answer($command, 'Name', 'Ana');
        $this->answer($command, 'Email', 'ana@example.com');
        $this->answer($command, 'Password', 'Senha-segura-1');

        return $this->answer($command, 'Confirm Password', 'Senha-segura-1');
    }

    public function test_reactivates_a_deleted_account_without_asking_for_a_password(): void
    {
        $user = User::factory()->create(['email' => 'ana@example.com']);
        $user->delete();

        $command = $this->command();
        $this->answer($command, 'Name', 'Ana Nova');
        $this->answer($command, 'Email', 'ana@example.com');

        $command->expectsOutputToContain('ana@example.com')->assertSuccessful();
        $command->run();

        $fresh = User::find($user->id);

        $this->assertSame('Ana Nova', $fresh->name);
        $this->assertNull($fresh->email_verified_at);
        $this->assertSame(1, User::withTrashed()->count());
    }

    public function test_creates_user_with_verified_email(): void
    {
        $this->freezeSecond();

        $this->answerValidData($this->command())
            ->expectsOutputToContain('ana@example.com')
            ->assertSuccessful()
            ->run();

        $user = User::where('email', 'ana@example.com')->firstOrFail();

        $this->assertSame('Ana', $user->name);
        $this->assertTrue(Hash::check('Senha-segura-1', $user->password));
        $this->assertTrue($user->email_verified_at->equalTo(now()));
        $this->assertNull($user->temporary_password_expires_at);
    }

    public function test_does_not_send_email(): void
    {
        $this->answerValidData($this->command())->assertSuccessful()->run();

        Notification::assertNothingSent();
    }

    public function test_asks_name_again_when_empty(): void
    {
        $command = $this->command();
        $this->answer($command, 'Name', '');

        $this->answerValidData($command)->assertSuccessful()->run();

        $this->assertSame(1, User::count());
    }

    public function test_asks_email_again_when_invalid(): void
    {
        $command = $this->command();
        $this->answer($command, 'Name', 'Ana');
        $this->answer($command, 'Email', 'email-invalido');
        $this->answer($command, 'Email', 'ana@example.com');
        $this->answer($command, 'Password', 'Senha-segura-1');
        $this->answer($command, 'Confirm Password', 'Senha-segura-1');

        $command->assertSuccessful()->run();

        $this->assertDatabaseHas('users', ['email' => 'ana@example.com']);
    }

    public function test_asks_email_again_when_already_registered(): void
    {
        User::factory()->create(['email' => 'existente@example.com']);

        $command = $this->command();
        $this->answer($command, 'Name', 'Ana');
        $this->answer($command, 'Email', 'existente@example.com');
        $this->answer($command, 'Email', 'ana@example.com');
        $this->answer($command, 'Password', 'Senha-segura-1');
        $this->answer($command, 'Confirm Password', 'Senha-segura-1');

        $command->assertSuccessful()->run();

        $this->assertSame(2, User::count());
    }

    public function test_asks_password_again_when_too_short(): void
    {
        $command = $this->command();
        $this->answer($command, 'Name', 'Ana');
        $this->answer($command, 'Email', 'ana@example.com');
        $this->answer($command, 'Password', 'curta');
        $this->answer($command, 'Password', 'Senha-segura-1');
        $this->answer($command, 'Confirm Password', 'Senha-segura-1');

        $command->assertSuccessful()->run();

        $this->assertTrue(Hash::check('Senha-segura-1', User::first()->password));
    }

    public function test_asks_both_passwords_again_when_they_differ(): void
    {
        $command = $this->command();
        $this->answer($command, 'Name', 'Ana');
        $this->answer($command, 'Email', 'ana@example.com');
        $this->answer($command, 'Password', 'Senha-segura-1');
        $this->answer($command, 'Confirm Password', 'senha-diferente');
        $this->answer($command, 'Password', 'Outra-senha-1');
        $this->answer($command, 'Confirm Password', 'Outra-senha-1');

        $command->expectsOutputToContain(__('The passwords do not match.'))->assertSuccessful()->run();

        $this->assertTrue(Hash::check('Outra-senha-1', User::first()->password));
    }

    public function test_fails_without_interaction(): void
    {
        $this->artisan('app:create-user', ['--no-interaction' => true])->assertFailed()->run();

        $this->assertSame(0, User::count());
    }
}
