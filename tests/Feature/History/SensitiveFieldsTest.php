<?php

namespace Tests\Feature\History;

use App\Models\ChangeHistory;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileWithHiddenDescription extends Profile
{
    protected $table = 'profiles';

    protected $hidden = ['description'];
}

class SensitiveFieldsTest extends TestCase
{
    use RefreshDatabase;

    private const MASK = '********';

    public function test_a_password_change_shows_only_masked_values(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();
        $previous = $user->getRawOriginal('password');

        $user->update(['password' => 'SenhaSuperSecreta1!']);

        $item = ChangeHistory::of($user)->where('operation', 'updated')->where('field', 'password')->sole();

        $this->assertSame(self::MASK, $item->old_value);
        $this->assertSame(self::MASK, $item->new_value);

        foreach (['SenhaSuperSecreta1!', $previous, $user->password] as $secret) {
            $this->assertSame(0, ChangeHistory::where('new_value', $secret)->orWhere('old_value', $secret)->count());
        }
    }

    public function test_the_remember_token_is_masked(): void
    {
        $user = User::factory()->withProfile(Profile::factory()->create())->create();

        $user->forceFill(['remember_token' => 'token-secreto'])->save();

        $this->assertSame(self::MASK, ChangeHistory::of($user)->where('operation', 'updated')->where('field', 'remember_token')->sole()->new_value);
    }

    public function test_hidden_attributes_are_masked_without_configuration(): void
    {
        $profile = ProfileWithHiddenDescription::forceCreate(['name' => 'Oculto', 'description' => 'Segredo']);

        $this->assertSame(self::MASK, ChangeHistory::of($profile)->where('field', 'description')->sole()->new_value);
    }

    public function test_the_masked_list_is_configurable(): void
    {
        config(['template.history.masked_fields' => ['description']]);

        $profile = Profile::factory()->create(['description' => 'Segredo']);

        $this->assertSame(self::MASK, ChangeHistory::of($profile)->where('field', 'description')->sole()->new_value);
    }

    public function test_an_empty_value_stays_empty(): void
    {
        config(['template.history.masked_fields' => ['description']]);

        $profile = Profile::factory()->create(['description' => null]);

        $this->assertNull(ChangeHistory::of($profile)->where('field', 'description')->sole()->new_value);
    }

    public function test_ignored_fields_are_left_out(): void
    {
        config(['template.history.ignored_fields' => ['description']]);

        $profile = Profile::factory()->create(['description' => 'Fora']);

        $profile->update(['description' => 'Ainda fora']);

        $this->assertCount(0, ChangeHistory::of($profile)->where('field', 'description')->get());
    }
}
