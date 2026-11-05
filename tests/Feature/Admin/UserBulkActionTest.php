<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class UserBulkActionTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function common(array $attributes = []): User
    {
        return User::factory()->withProfile(Profile::factory()->create())->create($attributes);
    }

    private function bulk(User $actor, string $action, array $ids): TestResponse
    {
        return $this->actingAs($actor)
            ->from('/admin/users')
            ->post('/admin/users/bulk', ['action' => $action, 'ids' => $ids]);
    }

    public function test_deactivates_the_selected_users(): void
    {
        $users = [$this->common(), $this->common()];

        $this->bulk(User::factory()->administrator()->create(), 'deactivate', [$users[0]->id, $users[1]->id])
            ->assertRedirect('/admin/users')
            ->assertSessionHas('flash.type', 'success');

        $this->assertFalse($users[0]->fresh()->is_active);
        $this->assertFalse($users[1]->fresh()->is_active);
    }

    public function test_activates_the_selected_users(): void
    {
        $user = $this->common(['is_active' => false]);

        $this->bulk(User::factory()->administrator()->create(), 'activate', [$user->id]);

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_deletes_the_selected_users_keeping_the_records(): void
    {
        $user = $this->common();

        $this->bulk(User::factory()->administrator()->create(), 'delete', [$user->id]);

        $this->assertSoftDeleted($user);
    }

    public function test_refused_records_are_skipped_and_the_rest_processed(): void
    {
        $administrator = User::factory()->administrator()->create();
        $common = $this->common();
        $actor = $this->userWith('visualizar_usuario', 'alterar_situacao_usuario');

        $response = $this->bulk($actor, 'deactivate', [$administrator->id, $common->id]);

        $this->assertTrue($administrator->fresh()->is_active);
        $this->assertFalse($common->fresh()->is_active);

        $response->assertSessionHas('flash.type', 'warning');
        $this->assertStringContainsString('1', session('flash.message'));
    }

    public function test_answer_informs_processed_and_skipped(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->bulk($administrator, 'delete', [$administrator->id, $this->common()->id]);

        $this->assertSame(
            __('Processed: :processed. Skipped: :skipped.', ['processed' => 1, 'skipped' => 1]),
            session('flash.message'),
        );
    }

    public function test_last_administrator_stays_protected(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->bulk($administrator, 'deactivate', [$administrator->id]);

        $this->assertTrue($administrator->fresh()->is_active);
    }

    public function test_without_the_permission_of_the_action_everything_is_skipped(): void
    {
        $user = $this->common();

        $this->bulk($this->userWith('visualizar_usuario'), 'delete', [$user->id]);

        $this->assertNotSoftDeleted($user);
    }

    public function test_route_requires_the_view_permission(): void
    {
        $this->bulk($this->userWith(), 'delete', [$this->common()->id])->assertForbidden();
    }

    public function test_action_and_ids_are_validated(): void
    {
        $actor = User::factory()->administrator()->create();

        $this->bulk($actor, 'outra', [1])->assertSessionHasErrors('action');
        $this->bulk($actor, 'delete', [])->assertSessionHasErrors('ids');
    }
}
