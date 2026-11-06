<?php

namespace Tests\Feature\Profile;

use App\Actions\Admin\DeleteUser;
use App\Actions\Admin\RestoreUser;
use App\Models\ChangeHistory;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AddressesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function address(array $data = []): array
    {
        return [
            'postal_code' => '01310-100',
            'street' => 'Avenida Paulista',
            'number' => '1000',
            'complement' => 'Sala 2',
            'reference' => 'Perto do metrô',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
            'country' => 'Brasil',
            ...$data,
        ];
    }

    public function test_the_first_address_becomes_the_main_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/addresses', $this->address())
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertDatabaseHas('user_enderecos', [
            'user_id' => $user->id,
            'postal_code' => '01310100',
            'complement' => 'Sala 2',
            'reference' => 'Perto do metrô',
            'is_main' => true,
        ]);

        $this->get('/profile')->assertInertia(fn (Assert $page) => $page
            ->has('addresses', 1)
            ->where('addresses.0.postal_code', '01310100')
            ->where('addresses.0.is_main', true)
            ->where('defaultCountry', 'Brasil'));
    }

    public function test_a_new_main_address_replaces_the_previous_one(): void
    {
        $user = User::factory()->create();
        $first = UserAddress::factory()->for($user)->main()->create();

        $this->actingAs($user)->post('/profile/addresses', $this->address(['is_main' => true]))
            ->assertSessionHasNoErrors();

        $this->assertFalse($first->refresh()->is_main);
        $this->assertSame(1, $user->addresses()->where('is_main', true)->count());
    }

    public function test_a_second_address_is_not_main_by_default(): void
    {
        $user = User::factory()->create();
        $first = UserAddress::factory()->for($user)->main()->create();

        $this->actingAs($user)->post('/profile/addresses', $this->address());

        $this->assertTrue($first->refresh()->is_main);
        $this->assertSame(1, $user->addresses()->where('is_main', true)->count());
    }

    public function test_the_main_address_cannot_be_unmarked(): void
    {
        $user = User::factory()->create();
        $address = UserAddress::factory()->for($user)->main()->create();

        $this->actingAs($user)->patch("/profile/addresses/{$address->id}", $this->address(['is_main' => false]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($address->refresh()->is_main);
    }

    public function test_editing_can_move_the_main_mark(): void
    {
        $user = User::factory()->create();
        $first = UserAddress::factory()->for($user)->main()->create();
        $second = UserAddress::factory()->for($user)->create();

        $this->actingAs($user)->patch("/profile/addresses/{$second->id}", $this->address(['is_main' => true]));

        $this->assertFalse($first->refresh()->is_main);
        $this->assertTrue($second->refresh()->is_main);
    }

    public function test_the_database_refuses_a_second_main_address(): void
    {
        $user = User::factory()->create();
        UserAddress::factory()->for($user)->main()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        UserAddress::factory()->for($user)->main()->create();
    }

    public function test_delivery_requires_the_recipient(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/addresses', $this->address(['is_delivery' => true]))
            ->assertSessionHasErrors('recipient_name');

        $this->post('/profile/addresses', $this->address(['is_delivery' => true, 'recipient_name' => 'João']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_enderecos', ['user_id' => $user->id, 'recipient_name' => 'João']);
    }

    public function test_the_recipient_is_not_kept_without_delivery(): void
    {
        $user = User::factory()->create();
        $address = UserAddress::factory()->for($user)->main()->create(['is_delivery' => true, 'recipient_name' => 'João']);

        $this->actingAs($user)->patch("/profile/addresses/{$address->id}", $this->address(['recipient_name' => 'Outro']))
            ->assertSessionHasNoErrors();

        $this->assertNull($address->refresh()->recipient_name);
    }

    public function test_billing_and_delivery_can_be_the_same_address(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/addresses', $this->address([
            'is_billing' => true,
            'is_delivery' => true,
            'recipient_name' => 'João',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_enderecos', ['user_id' => $user->id, 'is_billing' => true, 'is_delivery' => true]);
    }

    public function test_an_address_without_number_uses_the_marker(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/profile/addresses', $this->address(['number' => 'S/N']))
            ->assertSessionHasNoErrors();
    }

    public function test_editing_can_clear_the_optional_fields(): void
    {
        $user = User::factory()->create();
        $address = UserAddress::factory()->for($user)->main()->create(['complement' => 'Sala 2']);
        $data = $this->address();
        unset($data['complement']);

        $this->actingAs($user)->patch("/profile/addresses/{$address->id}", $data)->assertSessionHasNoErrors();

        $this->assertNull($address->refresh()->complement);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function requiredFields(): array
    {
        return array_map(fn (string $field) => [$field], array_combine(
            $fields = ['postal_code', 'street', 'number', 'neighborhood', 'city', 'state', 'country'],
            $fields,
        ));
    }

    #[DataProvider('requiredFields')]
    public function test_a_required_field_is_refused_when_empty(string $field): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/profile/addresses', $this->address([$field => '']))
            ->assertSessionHasErrors($field);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPostalCodes(): array
    {
        return ['curto' => ['01310-10'], 'longo' => ['01310-1000'], 'letras' => ['0131a-100']];
    }

    #[DataProvider('invalidPostalCodes')]
    public function test_an_invalid_brazilian_postal_code_is_refused(string $postalCode): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/profile/addresses', $this->address(['postal_code' => $postalCode]))
            ->assertSessionHasErrors('postal_code');
    }

    public function test_the_state_must_be_a_brazilian_one(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/profile/addresses', $this->address(['state' => 'XX']))
            ->assertSessionHasErrors('state');
    }

    public function test_another_country_accepts_free_formats(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/addresses', $this->address([
            'postal_code' => 'SW1A 1AA',
            'state' => 'England',
            'country' => 'United Kingdom',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_enderecos', ['user_id' => $user->id, 'postal_code' => 'SW1A 1AA']);
    }

    public function test_a_value_that_is_not_text_is_refused_without_breaking(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/profile/addresses', $this->address(['postal_code' => ['01310100'], 'country' => ['Brasil']]))
            ->assertSessionHasErrors(['postal_code', 'country']);
    }

    public function test_the_default_country_is_configurable(): void
    {
        config(['template.address.default_country' => 'Portugal']);

        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertInertia(fn (Assert $page) => $page->where('defaultCountry', 'Portugal'));
    }

    public function test_deleting_keeps_the_record_and_removes_it_from_the_list(): void
    {
        $user = User::factory()->create();
        $address = UserAddress::factory()->for($user)->create();

        $this->actingAs($user)->delete("/profile/addresses/{$address->id}")->assertRedirect('/profile');

        $this->assertSoftDeleted($address);

        $this->get('/profile')->assertInertia(fn (Assert $page) => $page->has('addresses', 0));
    }

    public function test_the_only_address_can_be_deleted_and_the_next_one_becomes_main(): void
    {
        $user = User::factory()->create();
        $address = UserAddress::factory()->for($user)->main()->create();

        $this->actingAs($user)->delete("/profile/addresses/{$address->id}")->assertSessionHasNoErrors();
        $this->post('/profile/addresses', $this->address())->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_enderecos', ['user_id' => $user->id, 'is_main' => true, 'deleted_at' => null]);
    }

    public function test_deleting_the_main_address_requires_choosing_the_next(): void
    {
        $user = User::factory()->create();
        $main = UserAddress::factory()->for($user)->main()->create();
        UserAddress::factory()->for($user)->create();

        $this->actingAs($user)->delete("/profile/addresses/{$main->id}")->assertSessionHasErrors('new_main_id');

        $this->assertNotSoftDeleted($main);
    }

    public function test_deleting_the_main_address_moves_the_mark(): void
    {
        $user = User::factory()->create();
        $main = UserAddress::factory()->for($user)->main()->create();
        $next = UserAddress::factory()->for($user)->create();

        $this->actingAs($user)->delete("/profile/addresses/{$main->id}", ['new_main_id' => $next->id])
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted($main);
        $this->assertTrue($next->refresh()->is_main);
    }

    public function test_the_next_main_address_must_be_of_the_same_person(): void
    {
        $user = User::factory()->create();
        $main = UserAddress::factory()->for($user)->main()->create();
        UserAddress::factory()->for($user)->create();
        $foreign = UserAddress::factory()->for(User::factory()->create())->create();

        $this->actingAs($user)->delete("/profile/addresses/{$main->id}", ['new_main_id' => $foreign->id])
            ->assertSessionHasErrors('new_main_id');

        $this->assertFalse($foreign->refresh()->is_main);
    }

    public function test_deleting_another_address_ignores_the_next_main(): void
    {
        $user = User::factory()->create();
        $main = UserAddress::factory()->for($user)->main()->create();
        $other = UserAddress::factory()->for($user)->create();
        $third = UserAddress::factory()->for($user)->create();

        $this->actingAs($user)->delete("/profile/addresses/{$other->id}", ['new_main_id' => $third->id])
            ->assertSessionHasNoErrors();

        $this->assertTrue($main->refresh()->is_main);
        $this->assertFalse($third->refresh()->is_main);
    }

    public function test_the_page_lists_only_the_own_addresses(): void
    {
        UserAddress::factory()->for(User::factory()->create())->create();

        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertInertia(fn (Assert $page) => $page->has('addresses', 0));
    }

    public function test_an_address_of_another_person_cannot_be_changed(): void
    {
        $address = UserAddress::factory()->for(User::factory()->create())->create();

        $this->actingAs(User::factory()->administrator()->create());

        $this->patch("/profile/addresses/{$address->id}", ['street' => ''])->assertNotFound();
        $this->delete("/profile/addresses/{$address->id}")->assertNotFound();

        $this->assertNotSoftDeleted($address);
    }

    public function test_a_deleted_address_cannot_be_changed(): void
    {
        $user = User::factory()->create();
        $address = UserAddress::factory()->for($user)->create();
        $address->delete();

        $this->actingAs($user)->patch("/profile/addresses/{$address->id}", $this->address())->assertNotFound();
    }

    public function test_the_request_cannot_write_on_another_account(): void
    {
        $other = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/addresses', $this->address(['user_id' => $other->id]));

        $this->assertDatabaseMissing('user_enderecos', ['user_id' => $other->id]);
    }

    public function test_the_history_records_the_address(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/addresses', $this->address());

        $this->assertSame('Avenida Paulista', ChangeHistory::where('table_name', 'user_enderecos')
            ->where('field', 'street')->first()->new_value);
    }

    public function test_the_addresses_stay_with_the_account_when_it_is_deleted_and_restored(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();
        UserAddress::factory()->for($user)->main()->create();

        app(DeleteUser::class)->handle($user);
        app(RestoreUser::class)->handle($user->refresh());

        $this->assertSame(1, $user->refresh()->addresses()->count());
    }
}
