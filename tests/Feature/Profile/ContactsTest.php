<?php

namespace Tests\Feature\Profile;

use App\Actions\Admin\DeleteUser;
use App\Actions\Admin\RestoreUser;
use App\Actions\SaveContact;
use App\Models\ChangeHistory;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactsTest extends TestCase
{
    use RefreshDatabase;

    private const MOBILE = '11987654321';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function phone(array $data = []): array
    {
        return ['type' => 'phone', 'value' => '(11) 98765-4321', ...$data];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function emergency(array $data = []): array
    {
        return $this->phone([
            'is_emergency' => true,
            'relationship' => 'Mother',
            'contact_name' => 'Maria Lima',
            ...$data,
        ]);
    }

    public function test_a_phone_is_saved_with_digits_only_and_listed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/contacts', $this->phone(['is_whatsapp' => true]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertDatabaseHas('user_contatos', [
            'user_id' => $user->id,
            'type' => 'phone',
            'value' => self::MOBILE,
            'is_whatsapp' => true,
        ]);

        $this->get('/profile')->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Edit')
            ->has('contacts', 1)
            ->where('contacts.0.value', self::MOBILE)
            ->where('contacts.0.is_whatsapp', true));
    }

    public function test_a_landline_is_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/contacts', $this->phone(['value' => '(21) 3456-7890']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_contatos', ['user_id' => $user->id, 'value' => '2134567890']);
    }

    public function test_an_email_is_saved_in_lowercase(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/contacts', ['type' => 'email', 'value' => 'Ana@Example.COM'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_contatos', ['user_id' => $user->id, 'value' => 'ana@example.com']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPhones(): array
    {
        return [
            'incompleto' => ['(11) 98765-432'],
            'longo demais' => ['(11) 98765-43210'],
            'ddd inexistente' => ['(10) 98765-4321'],
            'ddd 23' => ['(23) 3456-7890'],
            'ddd com zero' => ['(01) 3456-7890'],
            'celular sem o 9' => ['(11) 88765-4321'],
            'fixo com início inválido' => ['(11) 1456-7890'],
            'letras' => ['(11) 9876a-4321'],
            'estrangeiro' => ['+1 415 555 2671'],
        ];
    }

    #[DataProvider('invalidPhones')]
    public function test_an_invalid_phone_is_refused(string $value): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/contacts', $this->phone(['value' => $value]))
            ->assertSessionHasErrors('value');

        $this->assertDatabaseMissing('user_contatos', ['user_id' => $user->id]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidEmails(): array
    {
        return [
            'sem arroba' => ['ana.example.com'],
            'sem domínio' => ['ana@'],
            'domínio incompleto' => ['ana@localhost'],
            'espaço' => ['ana lima@example.com'],
        ];
    }

    #[DataProvider('invalidEmails')]
    public function test_an_invalid_email_is_refused(string $value): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/contacts', ['type' => 'email', 'value' => $value])
            ->assertSessionHasErrors('value');
    }

    public function test_an_unknown_type_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/profile/contacts', ['type' => 'fax', 'value' => self::MOBILE])
            ->assertSessionHasErrors('type');
    }

    public function test_a_value_that_is_not_text_is_refused_without_breaking(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/profile/contacts', $this->phone(['value' => [self::MOBILE]]))
            ->assertSessionHasErrors('value');
    }

    public function test_a_repeated_value_is_refused(): void
    {
        $user = User::factory()->create();
        UserContact::factory()->for($user)->create(['value' => self::MOBILE]);

        $this->actingAs($user)->post('/profile/contacts', $this->phone())->assertSessionHasErrors('value');

        $this->assertSame(1, $user->contacts()->count());
    }

    public function test_a_repeated_email_in_other_case_is_refused(): void
    {
        $user = User::factory()->create();
        UserContact::factory()->for($user)->email()->create(['value' => 'ana@example.com']);

        $this->actingAs($user)->post('/profile/contacts', ['type' => 'email', 'value' => 'ANA@example.com'])
            ->assertSessionHasErrors('value');
    }

    public function test_two_people_can_have_the_same_value(): void
    {
        UserContact::factory()->for(User::factory()->create())->create(['value' => self::MOBILE]);

        $this->actingAs(User::factory()->create())->post('/profile/contacts', $this->phone())
            ->assertSessionHasNoErrors();
    }

    public function test_a_deleted_value_can_be_added_again(): void
    {
        $user = User::factory()->create();
        UserContact::factory()->for($user)->create(['value' => self::MOBILE])->delete();

        $this->actingAs($user)->post('/profile/contacts', $this->phone())->assertSessionHasNoErrors();
    }

    public function test_a_simultaneous_repetition_becomes_a_validation_error_and_is_not_logged(): void
    {
        $user = User::factory()->create();
        UserContact::factory()->for($user)->create(['value' => self::MOBILE]);
        Log::spy();

        try {
            app(SaveContact::class)->handle($user, ['type' => 'phone', 'value' => self::MOBILE]);
            $this->fail('A repetição deveria ser recusada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('value', $exception->errors());
            $this->assertStringNotContainsString(self::MOBILE, $exception->getMessage());
        }

        Log::shouldNotHaveReceived('error');
    }

    public function test_whatsapp_is_not_kept_on_an_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/contacts', [
            'type' => 'email',
            'value' => 'ana@example.com',
            'is_whatsapp' => true,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_contatos', ['user_id' => $user->id, 'is_whatsapp' => false]);
    }

    public function test_a_contact_can_be_edited_keeping_its_value(): void
    {
        $user = User::factory()->create();
        $contact = UserContact::factory()->for($user)->create(['value' => self::MOBILE]);

        $this->actingAs($user)->patch("/profile/contacts/{$contact->id}", $this->phone(['is_whatsapp' => true]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue($contact->refresh()->is_whatsapp);
    }

    public function test_changing_a_phone_to_an_email_clears_whatsapp(): void
    {
        $user = User::factory()->create();
        $contact = UserContact::factory()->for($user)->create(['is_whatsapp' => true]);

        $this->actingAs($user)->patch("/profile/contacts/{$contact->id}", ['type' => 'email', 'value' => 'ana@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertFalse($contact->refresh()->is_whatsapp);
    }

    public function test_editing_to_a_value_of_another_contact_is_refused(): void
    {
        $user = User::factory()->create();
        UserContact::factory()->for($user)->create(['value' => self::MOBILE]);
        $contact = UserContact::factory()->for($user)->create(['value' => '2134567890']);

        $this->actingAs($user)->patch("/profile/contacts/{$contact->id}", $this->phone())
            ->assertSessionHasErrors('value');
    }

    public function test_deleting_keeps_the_record_and_removes_it_from_the_list(): void
    {
        $user = User::factory()->create();
        $contact = UserContact::factory()->for($user)->create();

        $this->actingAs($user)->delete("/profile/contacts/{$contact->id}")->assertRedirect('/profile');

        $this->assertSoftDeleted($contact);

        $this->get('/profile')->assertInertia(fn (Assert $page) => $page->has('contacts', 0));
    }

    public function test_an_email_contact_does_not_change_the_login_email(): void
    {
        $user = User::factory()->create(['email' => 'login@example.com']);

        $this->actingAs($user)->post('/profile/contacts', ['type' => 'email', 'value' => 'outro@example.com']);

        $this->assertSame('login@example.com', $user->refresh()->email);
    }

    public function test_an_emergency_contact_keeps_the_name_and_the_relationship(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/contacts', $this->emergency())->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_contatos', [
            'user_id' => $user->id,
            'is_emergency' => true,
            'relationship' => 'Mother',
            'contact_name' => 'Maria Lima',
        ]);

        $this->get('/profile')->assertInertia(fn (Assert $page) => $page
            ->where('contacts.0.relationship', 'Mother')
            ->where('contacts.0.relationship_label', 'Mãe')
            ->where('contacts.0.contact_name', 'Maria Lima'));
    }

    public function test_an_email_can_be_an_emergency_contact(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/profile/contacts', $this->emergency(['type' => 'email', 'value' => 'maria@example.com']))
            ->assertSessionHasNoErrors();
    }

    public function test_an_emergency_contact_requires_the_name_and_the_relationship(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/contacts', $this->phone(['is_emergency' => true]))
            ->assertSessionHasErrors(['relationship', 'contact_name']);

        $this->assertDatabaseMissing('user_contatos', ['user_id' => $user->id]);
    }

    public function test_a_relationship_outside_the_list_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/profile/contacts', $this->emergency(['relationship' => 'Vizinho']))
            ->assertSessionHasErrors('relationship');
    }

    public function test_the_name_and_the_relationship_are_not_kept_without_the_option(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/contacts', $this->emergency(['is_emergency' => false]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_contatos', [
            'user_id' => $user->id,
            'is_emergency' => false,
            'relationship' => null,
            'contact_name' => null,
        ]);
    }

    public function test_unchecking_the_option_clears_the_name_and_the_relationship(): void
    {
        $user = User::factory()->create();
        $contact = UserContact::factory()->for($user)->emergency()->create(['value' => self::MOBILE]);

        $this->actingAs($user)->patch("/profile/contacts/{$contact->id}", $this->phone(['is_emergency' => false]))
            ->assertSessionHasNoErrors();

        $contact->refresh();

        $this->assertNull($contact->relationship);
        $this->assertNull($contact->contact_name);
    }

    public function test_the_relationship_list_ends_with_other_and_is_translated(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertInertia(fn (Assert $page) => $page->where('relationships', [
                ['value' => 'Mother', 'label' => 'Mãe'],
                ['value' => 'Father', 'label' => 'Pai'],
                ['value' => 'Spouse or partner', 'label' => 'Cônjuge ou companheiro(a)'],
                ['value' => 'Child', 'label' => 'Filho(a)'],
                ['value' => 'Sibling', 'label' => 'Irmão(ã)'],
                ['value' => 'Grandparent', 'label' => 'Avô/Avó'],
                ['value' => 'Uncle or aunt', 'label' => 'Tio(a)'],
                ['value' => 'Cousin', 'label' => 'Primo(a)'],
                ['value' => 'Friend', 'label' => 'Amigo(a)'],
                ['value' => 'Other relationship', 'label' => 'Outro'],
            ]));
    }

    public function test_the_relationship_list_follows_the_configuration(): void
    {
        config(['template.profile.relationships' => ['Neighbor']]);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/contacts', $this->emergency())->assertSessionHasErrors('relationship');

        $this->post('/profile/contacts', $this->emergency(['relationship' => 'Neighbor']))->assertSessionHasNoErrors();
    }

    public function test_the_page_lists_only_the_own_contacts(): void
    {
        UserContact::factory()->for(User::factory()->create())->create();

        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertInertia(fn (Assert $page) => $page->has('contacts', 0));
    }

    public function test_a_contact_of_another_person_cannot_be_changed(): void
    {
        $contact = UserContact::factory()->for(User::factory()->create())->create(['value' => self::MOBILE]);

        $this->actingAs(User::factory()->create());

        $this->patch("/profile/contacts/{$contact->id}", $this->phone(['value' => '(21) 3456-7890']))->assertNotFound();
        $this->delete("/profile/contacts/{$contact->id}")->assertNotFound();

        $this->assertNotSoftDeleted($contact);
        $this->assertSame(self::MOBILE, $contact->refresh()->value);
    }

    public function test_another_person_gets_the_same_answer_with_an_invalid_payload(): void
    {
        $contact = UserContact::factory()->for(User::factory()->create())->create();

        $this->actingAs(User::factory()->create())
            ->patch("/profile/contacts/{$contact->id}", ['type' => 'fax'])
            ->assertNotFound();
    }

    public function test_an_administrator_has_no_access_through_this_page(): void
    {
        $contact = UserContact::factory()->for(User::factory()->create())->create();

        $this->actingAs(User::factory()->administrator()->create())
            ->delete("/profile/contacts/{$contact->id}")
            ->assertNotFound();
    }

    public function test_a_deleted_contact_cannot_be_changed(): void
    {
        $user = User::factory()->create();
        $contact = UserContact::factory()->for($user)->create();
        $contact->delete();

        $this->actingAs($user)->patch("/profile/contacts/{$contact->id}", $this->phone())->assertNotFound();
    }

    public function test_the_request_cannot_write_on_another_account(): void
    {
        $other = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/contacts', $this->phone(['user_id' => $other->id]));

        $this->assertDatabaseMissing('user_contatos', ['user_id' => $other->id]);
        $this->assertDatabaseHas('user_contatos', ['user_id' => $user->id]);
    }

    public function test_visitors_are_sent_to_the_login(): void
    {
        $this->post('/profile/contacts', $this->phone())->assertRedirect('/login');
    }

    public function test_the_history_records_the_contact(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/contacts', $this->phone());

        $contact = $user->contacts()->first();
        $contact->delete();

        $rows = ChangeHistory::where('table_name', 'user_contatos')->get();

        $this->assertSame(self::MOBILE, $rows->firstWhere('field', 'value')->new_value);
        $this->assertTrue($rows->contains('field', 'deleted_at'));
    }

    public function test_the_contacts_stay_with_the_account_when_it_is_deleted_and_restored(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();
        $contact = UserContact::factory()->for($user)->create();

        app(DeleteUser::class)->handle($user);
        app(RestoreUser::class)->handle($user->refresh());

        $this->assertNotSoftDeleted($contact);
        $this->assertSame(1, $user->refresh()->contacts()->count());
    }

    public function test_the_shared_user_does_not_carry_the_contacts(): void
    {
        $user = User::factory()->create();
        UserContact::factory()->for($user)->create();

        $this->actingAs($user)->get('/profile')
            ->assertInertia(fn (Assert $page) => $page->missing('auth.user.contacts'));
    }
}
