<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\ProfilePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesAdminData;
use Tests\TestCase;

class DeleteProfileTest extends TestCase
{
    use CreatesAdminData, RefreshDatabase;

    private function destroy(Profile $profile): TestResponse
    {
        return $this->actingAs(User::factory()->administrator()->create())
            ->from('/admin/profiles')
            ->delete("/admin/profiles/{$profile->id}");
    }

    public function test_profile_without_users_is_deleted_keeping_record_and_links(): void
    {
        $profile = $this->profileWith('gerar_relatorio');

        $this->destroy($profile)->assertSessionHas('flash.type', 'success');

        $this->assertSoftDeleted($profile);
        $this->assertSame(1, ProfilePermission::where('profile_id', $profile->id)->count());
    }

    public function test_master_profile_is_refused_even_without_users(): void
    {
        $this->destroy(Profile::administrator())->assertSessionHasErrors('profile');

        $this->assertNotSoftDeleted(Profile::administrator());
    }

    public function test_default_profile_is_refused(): void
    {
        $this->destroy(Profile::default())->assertSessionHasErrors('profile');

        $this->assertNotSoftDeleted(Profile::default());
    }

    public function test_profile_with_users_is_refused_with_the_reason(): void
    {
        $profile = Profile::factory()->create();
        User::factory()->withProfile($profile)->create();

        $this->destroy($profile)->assertSessionHasErrors('profile');

        $this->assertNotSoftDeleted($profile);
    }

    public function test_profile_with_only_deleted_users_is_deleted(): void
    {
        $profile = Profile::factory()->create();
        User::factory()->withProfile($profile)->create()->delete();

        $this->destroy($profile)->assertSessionHasNoErrors();

        $this->assertSoftDeleted($profile);
    }

    public function test_non_administrator_cannot_delete_the_master_profile(): void
    {
        $this->actingAs($this->userWith('excluir_perfil'))
            ->delete('/admin/profiles/'.Profile::administrator()->id)
            ->assertForbidden();
    }
}
