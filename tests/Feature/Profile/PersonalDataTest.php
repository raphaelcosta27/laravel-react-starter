<?php

namespace Tests\Feature\Profile;

use App\Actions\UpdateProfile;
use App\Models\ChangeHistory;
use App\Models\User;
use App\Models\UserData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PersonalDataTest extends TestCase
{
    use RefreshDatabase;

    private const CPF = '52998224725';

    private const OTHER_CPF = '11144477735';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(User $user, array $data = []): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'preferred_name' => 'Ana',
            'birth_date' => '1990-05-20',
            'gender' => 'Female',
            'cpf' => '529.982.247-25',
            ...$data,
        ];
    }

    public function test_the_data_is_saved_and_shown_on_return(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->get('/profile')->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Edit')
            ->where('personalData.preferred_name', 'Ana')
            ->where('personalData.birth_date', '1990-05-20')
            ->where('personalData.gender', 'Female')
            ->where('personalData.cpf', self::CPF));
    }

    public function test_the_cpf_is_stored_without_the_mask(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user));

        $this->assertDatabaseHas('user_dados', ['user_id' => $user->id, 'cpf' => self::CPF]);
    }

    public function test_an_account_without_data_opens_the_page_empty(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('personalData.preferred_name', null)
                ->where('personalData.cpf', null));
    }

    public function test_only_the_full_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Ana Lima',
            'email' => $user->email,
            'preferred_name' => null,
            'birth_date' => null,
            'gender' => null,
            'cpf' => null,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_dados', ['user_id' => $user->id, 'full_name' => 'Ana Lima', 'cpf' => null]);

        $this->patch('/profile', $this->payload($user, ['name' => '']))->assertSessionHasErrors('name');
    }

    public function test_saving_twice_keeps_a_single_record(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user));
        $this->patch('/profile', $this->payload($user, ['preferred_name' => 'Aninha']));

        $this->assertSame(1, UserData::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('user_dados', ['user_id' => $user->id, 'preferred_name' => 'Aninha']);
    }

    public function test_a_request_without_personal_fields_leaves_them_untouched(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user));
        $this->patch('/profile', ['name' => 'Ana Lima', 'email' => $user->email])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_dados', ['user_id' => $user->id, 'cpf' => self::CPF, 'preferred_name' => 'Ana']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidCpfs(): array
    {
        return [
            'dígito errado' => ['529.982.247-26'],
            'segundo dígito errado' => ['52998224734'],
            'dígitos iguais' => ['111.111.111-11'],
            'zeros' => ['00000000000'],
            'curto' => ['5299822472'],
            'longo' => ['529982247250'],
            'letras' => ['abc.def.ghi-jk'],
            'mistura' => ['5299822472a'],
        ];
    }

    #[DataProvider('invalidCpfs')]
    public function test_an_invalid_cpf_is_refused(string $cpf): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user, ['cpf' => $cpf]))
            ->assertSessionHasErrors('cpf');

        $this->assertDatabaseMissing('user_dados', ['user_id' => $user->id]);
    }

    public function test_a_value_that_is_not_text_is_refused_without_breaking(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user, ['cpf' => [self::CPF]]))
            ->assertSessionHasErrors('cpf');
    }

    public function test_a_cpf_of_another_account_is_refused_without_naming_it(): void
    {
        $owner = User::factory()->create(['name' => 'Dona do CPF']);
        UserData::factory()->for($owner)->create(['cpf' => self::CPF]);
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user))->assertSessionHasErrors('cpf');

        $message = session('errors')->first('cpf');
        $this->assertStringNotContainsString($owner->name, $message);
        $this->assertStringNotContainsString($owner->email, $message);
    }

    public function test_the_person_can_keep_the_own_cpf(): void
    {
        $user = User::factory()->create();
        UserData::factory()->for($user)->create(['cpf' => self::CPF]);

        $this->actingAs($user)->patch('/profile', $this->payload($user))->assertSessionHasNoErrors();
    }

    public function test_a_simultaneous_cpf_conflict_becomes_a_validation_error_and_is_not_logged(): void
    {
        UserData::factory()->for(User::factory()->create())->create(['cpf' => self::CPF]);
        $user = User::factory()->create();
        Log::spy();

        try {
            app(UpdateProfile::class)->handle($user, ['name' => $user->name, 'email' => $user->email, 'cpf' => self::CPF]);
            $this->fail('O conflito deveria ser recusado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cpf', $exception->errors());
            $this->assertStringNotContainsString(self::CPF, $exception->getMessage());
        }

        Log::shouldNotHaveReceived('error');
        $this->assertDatabaseMissing('user_dados', ['user_id' => $user->id]);
    }

    public function test_the_cpf_can_be_required_by_configuration(): void
    {
        config(['template.profile.cpf_required' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user, ['cpf' => null]))
            ->assertSessionHasErrors('cpf');

        $this->get('/profile')->assertInertia(fn (Assert $page) => $page->where('cpfRequired', true));
    }

    public function test_the_gender_list_ends_with_other_and_is_translated(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertInertia(fn (Assert $page) => $page->where('genders', [
                ['value' => 'Female', 'label' => 'Feminino'],
                ['value' => 'Male', 'label' => 'Masculino'],
                ['value' => 'Non-binary', 'label' => 'Não binário'],
                ['value' => 'Prefer not to say', 'label' => 'Prefiro não informar'],
                ['value' => 'Other', 'label' => 'Outros'],
            ]));
    }

    public function test_the_gender_list_follows_the_configuration(): void
    {
        config(['template.profile.genders' => ['Agender', 'Other']]);
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user, ['gender' => 'Female']))
            ->assertSessionHasErrors('gender');

        $this->patch('/profile', $this->payload($user, ['gender' => 'Agender']))->assertSessionHasNoErrors();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidBirthDates(): array
    {
        return [
            'futura' => ['2999-01-01'],
            'inexistente' => ['1990-02-31'],
            'texto' => ['ontem'],
            'outro formato' => ['20/05/1990'],
            'antiga demais' => ['0001-01-01'],
        ];
    }

    #[DataProvider('invalidBirthDates')]
    public function test_an_invalid_birth_date_is_refused(string $date): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user, ['birth_date' => $date]))
            ->assertSessionHasErrors('birth_date');
    }

    public function test_today_is_a_valid_birth_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user, ['birth_date' => now()->toDateString()]))
            ->assertSessionHasNoErrors();
    }

    public function test_the_page_shows_only_the_own_data(): void
    {
        UserData::factory()->for(User::factory()->create())->create(['cpf' => self::OTHER_CPF]);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')
            ->assertInertia(fn (Assert $page) => $page->where('personalData.cpf', null));
    }

    public function test_the_request_cannot_write_on_another_account(): void
    {
        $other = User::factory()->create();
        UserData::factory()->for($other)->create(['preferred_name' => 'Outra']);
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user, ['user_id' => $other->id]));

        $this->assertDatabaseHas('user_dados', ['user_id' => $other->id, 'preferred_name' => 'Outra']);
    }

    public function test_the_history_records_the_data_with_the_cpf_masked(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user));

        $rows = ChangeHistory::where('table_name', 'user_dados')->get();

        $this->assertSame('Ana', $rows->firstWhere('field', 'preferred_name')->new_value);
        $this->assertSame('********', $rows->firstWhere('field', 'cpf')->new_value);
        $this->assertStringNotContainsString(self::CPF, $rows->toJson());
    }

    public function test_the_cpf_never_leaves_the_model_by_serialization(): void
    {
        $data = UserData::factory()->for(User::factory()->create())->create(['cpf' => self::CPF])->refresh();

        $this->assertStringNotContainsString(self::CPF, $data->toJson());
    }

    public function test_a_refused_cpf_does_not_stay_in_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user, ['cpf' => '529.982.247-26']));

        $this->assertNull(session()->getOldInput('cpf'));
    }

    public function test_the_panel_listing_and_export_do_not_show_the_cpf(): void
    {
        $administrator = User::factory()->administrator()->create();
        $person = User::factory()->create();
        UserData::factory()->for($person)->create(['cpf' => self::CPF]);

        $this->actingAs($administrator);

        $this->assertStringNotContainsString(self::CPF, (string) $this->get('/admin/users')->getContent());
        $this->assertStringNotContainsString(self::CPF, (string) $this->get('/admin/users/export?format=csv')->streamedContent());
    }
}
