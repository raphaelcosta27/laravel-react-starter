<?php

namespace Tests\Feature\Profile;

use App\Actions\Admin\DeleteUser;
use App\Actions\Admin\ReactivateUser;
use App\Actions\Admin\RestoreUser;
use App\Actions\Admin\UpdateUser;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PersonalDataLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private const CPF = '52998224725';

    private function userWithData(): User
    {
        $profile = Profile::factory()->create();
        $user = User::factory()->withProfile($profile)->create();
        UserData::factory()->for($user)->create(['cpf' => self::CPF, 'full_name' => $user->name]);

        return $user;
    }

    public function test_the_account_name_changes_with_the_full_name(): void
    {
        $user = $this->userWithData();

        $this->actingAs($user)->patch('/profile', ['name' => 'Novo Nome', 'email' => $user->email]);

        $this->assertSame('Novo Nome', $user->refresh()->name);
        $this->assertDatabaseHas('user_dados', ['user_id' => $user->id, 'full_name' => 'Novo Nome']);
    }

    public function test_a_name_changed_in_the_panel_reaches_the_full_name(): void
    {
        $user = $this->userWithData();

        app(UpdateUser::class)->handle($user, ['name' => 'Nome do Painel']);

        $this->assertDatabaseHas('user_dados', ['user_id' => $user->id, 'full_name' => 'Nome do Painel']);
    }

    public function test_a_full_name_written_on_the_record_reaches_the_account(): void
    {
        $user = $this->userWithData();

        $user->personalData->update(['full_name' => 'Nome Direto']);

        $this->assertSame('Nome Direto', $user->refresh()->name);
    }

    public function test_a_mass_update_keeps_both_names_equal(): void
    {
        $user = $this->userWithData();

        User::whereKey($user->id)->update(['name' => 'Nome em Massa']);

        $this->assertDatabaseHas('user_dados', ['user_id' => $user->id, 'full_name' => 'Nome em Massa']);
    }

    public function test_a_new_record_repeats_the_account_name(): void
    {
        $user = User::factory()->create(['name' => 'Nome da Conta']);

        $user->personalData()->create(['preferred_name' => 'Nome']);

        $this->assertDatabaseHas('user_dados', ['user_id' => $user->id, 'full_name' => 'Nome da Conta']);
    }

    public function test_deleting_the_account_preserves_the_data_and_frees_the_cpf(): void
    {
        $user = $this->userWithData();

        app(DeleteUser::class)->handle($user);

        $this->assertSoftDeleted('user_dados', ['user_id' => $user->id, 'cpf' => self::CPF]);

        $other = User::factory()->create();

        $this->actingAs($other)->patch('/profile', [
            'name' => $other->name,
            'email' => $other->email,
            'cpf' => self::CPF,
        ])->assertSessionHasNoErrors();
    }

    public function test_restoring_the_account_brings_the_data_back(): void
    {
        $user = $this->userWithData();
        app(DeleteUser::class)->handle($user);

        app(RestoreUser::class)->handle($user->refresh());

        $this->assertNotSoftDeleted('user_dados', ['user_id' => $user->id, 'cpf' => self::CPF]);
    }

    public function test_restoring_an_account_without_data_works(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();
        app(DeleteUser::class)->handle($user);

        app(RestoreUser::class)->handle($user->refresh());

        $this->assertFalse($user->refresh()->trashed());
    }

    public function test_restoring_is_refused_when_the_cpf_is_taken(): void
    {
        $user = $this->userWithData();
        app(DeleteUser::class)->handle($user);
        UserData::factory()->for(User::factory()->create())->create(['cpf' => self::CPF]);

        try {
            app(RestoreUser::class)->handle($user->refresh());
            $this->fail('A restauração deveria ser recusada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('user', $exception->errors());
        }

        $this->assertTrue($user->refresh()->trashed());
        $this->assertSoftDeleted('user_dados', ['user_id' => $user->id]);
    }

    public function test_a_new_registration_of_a_deleted_email_brings_the_data_back_with_the_new_name(): void
    {
        Notification::fake();
        $user = $this->userWithData();
        app(DeleteUser::class)->handle($user);

        app(ReactivateUser::class)->handle($user->refresh(), 'Nome Novo', Profile::factory()->create());

        $this->assertDatabaseHas('user_dados', ['user_id' => $user->id, 'full_name' => 'Nome Novo', 'deleted_at' => null]);
    }

    public function test_a_new_registration_is_refused_when_the_cpf_is_taken(): void
    {
        Notification::fake();
        $user = $this->userWithData();
        app(DeleteUser::class)->handle($user);
        UserData::factory()->for(User::factory()->create())->create(['cpf' => self::CPF]);

        try {
            app(ReactivateUser::class)->handle($user->refresh(), 'Nome Novo', Profile::factory()->create());
            $this->fail('A reativação deveria ser recusada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
        }

        $this->assertTrue($user->refresh()->trashed());
    }
}
