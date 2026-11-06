<?php

namespace Tests\Feature\Dashboard;

use App\Models\ChangeHistory;
use App\Models\ScreenNotification;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserContact;
use App\Models\UserData;
use App\Support\Dashboard\Blocks\MyActivity;
use App\Support\Dashboard\Blocks\Notifications;
use App\Support\Dashboard\Blocks\ProfileCompletion;
use App\Support\ScreenNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PersonalBlocksTest extends TestCase
{
    use RefreshDatabase;

    private function completeProfile(User $user): void
    {
        UserData::factory()->for($user)->create([
            'birth_date' => '1990-05-20',
            'gender' => 'Other',
            'cpf' => '52998224725',
            'avatar_path' => 'avatars/a.jpg',
        ]);
        UserContact::factory()->for($user)->create();
        UserContact::factory()->emergency()->for($user)->create();
        UserAddress::factory()->main()->for($user)->create();
    }

    public function test_an_empty_profile_is_at_zero_with_every_item_missing(): void
    {
        $data = app(ProfileCompletion::class)->data(User::factory()->create());

        $this->assertSame(0, $data['percent']);
        $this->assertSame(
            ['personal_data', 'avatar', 'contact', 'emergency_contact', 'main_address'],
            array_column($data['missing'], 'key'),
        );
        $this->assertSame(route('profile.edit').'#contacts', $data['missing'][2]['url']);
    }

    public function test_a_complete_profile_is_at_one_hundred(): void
    {
        $user = User::factory()->create();
        $this->completeProfile($user);

        $data = app(ProfileCompletion::class)->data($user);

        $this->assertSame(100, $data['percent']);
        $this->assertSame([], $data['missing']);
    }

    public function test_personal_data_needs_birth_date_gender_and_cpf(): void
    {
        $user = User::factory()->create();
        UserData::factory()->for($user)->create(['birth_date' => '1990-05-20', 'gender' => 'Other', 'cpf' => null]);

        $data = app(ProfileCompletion::class)->data($user);

        $this->assertContains('personal_data', array_column($data['missing'], 'key'));
    }

    public function test_a_deleted_contact_does_not_count(): void
    {
        $user = User::factory()->create();
        UserContact::factory()->for($user)->create()->delete();

        $data = app(ProfileCompletion::class)->data($user);

        $this->assertContains('contact', array_column($data['missing'], 'key'));
    }

    public function test_an_address_that_is_not_main_does_not_count(): void
    {
        $user = User::factory()->create();
        UserAddress::factory()->for($user)->create(['is_main' => false]);

        $data = app(ProfileCompletion::class)->data($user);

        $this->assertContains('main_address', array_column($data['missing'], 'key'));
    }

    public function test_only_the_configured_items_count(): void
    {
        config(['template.dashboard.profile_items' => ['contact', 'avatar']]);
        $user = User::factory()->create();
        UserContact::factory()->for($user)->create();

        $data = app(ProfileCompletion::class)->data($user);

        $this->assertSame(50, $data['percent']);
        $this->assertSame(['avatar'], array_column($data['missing'], 'key'));
    }

    public function test_notifications_show_the_unread_count_and_the_five_latest(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 7) as $number) {
            ScreenNotifications::send($user, "Aviso {$number}", 'Texto');
        }

        $data = app(Notifications::class)->data($user);

        $this->assertSame(7, $data['unread']);
        $this->assertCount(5, $data['items']);
        $this->assertSame('Aviso 7', $data['items'][0]['title']);
    }

    public function test_hidden_notifications_do_not_appear(): void
    {
        $user = User::factory()->create();
        ScreenNotifications::send($user, 'Oculta', 'Texto');
        ScreenNotification::first()->delete();

        $this->assertSame([], app(Notifications::class)->data($user)['items']);
    }

    public function test_opening_the_screen_does_not_mark_notifications_as_seen(): void
    {
        $user = User::factory()->create();
        ScreenNotifications::send($user, 'Aviso', 'Texto');

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->assertSame(1, ScreenNotifications::unread($user));
    }

    public function test_the_notifications_block_depends_on_the_feature(): void
    {
        config(['template.notifications.enabled' => false]);

        $this->assertFalse(app(Notifications::class)->available());
    }

    public function test_activity_shows_one_item_per_action_without_values(): void
    {
        $user = User::factory()->create();
        Auth::login($user);
        $contact = UserContact::factory()->for($user)->create(['value' => '11988887777', 'contact_name' => 'Nome Secreto']);
        $contact->update(['value' => '11977776666', 'contact_name' => 'Outro Secreto']);

        $data = app(MyActivity::class)->data($user);
        $encoded = json_encode($data);

        $this->assertSame('Contato', $data['items'][0]['entity']);
        $this->assertSame('Alterado', $data['items'][0]['action']);
        $this->assertContains('Telefone ou e-mail', $data['items'][0]['fields']);
        $this->assertStringNotContainsString('11977776666', $encoded);
        $this->assertStringNotContainsString('Secreto', $encoded);
    }

    public function test_only_an_update_lists_the_fields(): void
    {
        $user = User::factory()->create();
        Auth::login($user);
        UserContact::factory()->for($user)->create();

        $item = app(MyActivity::class)->data($user)['items'][0];

        $this->assertSame('Criado', $item['action']);
        $this->assertSame([], $item['fields']);
    }

    public function test_activity_keeps_the_five_latest_actions(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        foreach (range(1, 7) as $number) {
            $user->update(['name' => "Nome {$number}"]);
        }

        $this->assertCount(5, app(MyActivity::class)->data($user)['items']);
    }

    public function test_activity_ignores_what_other_people_did(): void
    {
        $user = User::factory()->create();
        Auth::login(User::factory()->administrator()->create());
        $user->update(['name' => 'Alterado por outra pessoa']);

        $this->assertSame([], app(MyActivity::class)->data($user)['items']);
    }

    public function test_activity_ignores_fields_outside_the_catalog(): void
    {
        $user = User::factory()->create();
        Auth::login($user);
        $user->forceFill(['remember_token' => 'novo-token'])->save();

        $this->assertSame([], app(MyActivity::class)->data($user)['items']);
        $this->assertGreaterThan(0, ChangeHistory::where('user_id', $user->id)->count());
    }
}
