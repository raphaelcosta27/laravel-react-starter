<?php

namespace Tests\Feature\Admin;

use App\Actions\Admin\DeleteUser;
use App\Models\User;
use App\Models\UserData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function manager(): User
    {
        return $this->userWith('visualizar_usuario');
    }

    private function withPicture(string $path = 'avatars/foto.png'): User
    {
        $user = User::factory()->create();

        Storage::disk('local')->put($path, 'imagem');
        UserData::factory()->for($user)->create(['avatar_path' => $path]);

        return $user;
    }

    public function test_the_picture_is_served_to_who_can_view_users(): void
    {
        $response = $this->actingAs($this->manager())
            ->get(route('admin.users.avatar', $this->withPicture()));

        $response->assertOk();
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('immutable', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_a_user_without_picture_answers_not_found(): void
    {
        $this->actingAs($this->manager())
            ->get(route('admin.users.avatar', User::factory()->create()))
            ->assertNotFound();
    }

    public function test_the_picture_is_refused_without_the_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.users.avatar', $this->withPicture()))
            ->assertForbidden();
    }

    public function test_the_picture_requires_a_session(): void
    {
        $this->get(route('admin.users.avatar', $this->withPicture()))
            ->assertRedirect(route('login'));
    }

    public function test_the_picture_of_a_deleted_user_is_still_served(): void
    {
        $user = $this->withPicture();

        app(DeleteUser::class)->handle($user);

        $this->actingAs($this->manager())
            ->get(route('admin.users.avatar', $user->id))
            ->assertOk();
    }

    public function test_the_list_sends_the_address_of_the_picture(): void
    {
        $user = $this->withPicture('avatars/abc.png');

        $this->actingAs($this->manager())
            ->get(route('admin.users.index', ['search' => $user->email]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.data.0.avatar_url', route('admin.users.avatar', ['user' => $user->id, 'v' => 'abc']))
            );
    }

    public function test_a_new_picture_changes_the_address(): void
    {
        $user = $this->withPicture('avatars/abc.png');

        $before = $user->managedAvatarUrl();

        $user->personalData->update(['avatar_path' => 'avatars/def.png']);

        $this->assertNotSame($before, $user->refresh()->managedAvatarUrl());
    }

    public function test_the_list_without_picture_sends_nothing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->manager())
            ->get(route('admin.users.index', ['search' => $user->email]))
            ->assertInertia(fn (Assert $page) => $page->where('rows.data.0.avatar_url', null));
    }

    public function test_the_deleted_tab_keeps_the_address_of_the_picture(): void
    {
        $user = $this->withPicture('avatars/abc.png');

        app(DeleteUser::class)->handle($user);

        $this->actingAs($this->userWith('visualizar_usuario', 'restaurar_usuario'))
            ->get(route('admin.users.index', ['tab' => 'trashed']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.data.0.avatar_url', route('admin.users.avatar', ['user' => $user->id, 'v' => 'abc']))
            );
    }

    public function test_the_list_does_not_query_each_row(): void
    {
        $manager = $this->manager()->load('personalData');

        $count = function () use ($manager): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->actingAs($manager)->get(route('admin.users.index'))->assertOk();

            return collect(DB::getQueryLog())
                ->filter(fn (array $entry) => str_contains($entry['query'], 'user_dados'))
                ->count();
        };

        $this->withPicture('avatars/a.png');
        $few = $count();

        $this->withPicture('avatars/b.png');
        $this->withPicture('avatars/c.png');

        $this->assertSame($few, $count());
    }
}
