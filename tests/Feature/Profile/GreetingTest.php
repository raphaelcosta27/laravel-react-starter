<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Models\UserData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GreetingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_greeting_uses_the_preferred_name(): void
    {
        $user = User::factory()->create(['name' => 'Ana Maria Lima']);
        UserData::factory()->for($user)->create(['preferred_name' => 'Aninha']);

        $this->actingAs($user)->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('auth.displayName', 'Aninha'));
    }

    public function test_the_greeting_falls_back_to_the_account_name(): void
    {
        $user = User::factory()->create(['name' => 'Ana Maria Lima']);

        $this->actingAs($user)->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('auth.displayName', 'Ana Maria Lima'));
    }

    public function test_an_empty_preferred_name_falls_back_to_the_account_name(): void
    {
        $user = User::factory()->create(['name' => 'Ana Maria Lima']);
        UserData::factory()->for($user)->create(['preferred_name' => null]);

        $this->actingAs($user)->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('auth.displayName', 'Ana Maria Lima'));
    }

    public function test_the_shared_user_does_not_carry_the_personal_data(): void
    {
        $user = User::factory()->create();
        UserData::factory()->for($user)->create(['preferred_name' => 'Aninha', 'cpf' => '52998224725']);

        $this->actingAs($user)->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->missing('auth.user.personal_data'));
    }

    public function test_visitors_have_no_identity(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('auth.displayName', null)
            ->where('auth.avatarUrl', null));
    }
}
