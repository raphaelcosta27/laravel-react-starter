<?php

namespace Tests\Feature\Profile;

use App\Models\ChangeHistory;
use App\Models\User;
use App\Models\UserData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function png(): UploadedFile
    {
        return $this->upload('foto.png', (string) base64_decode(self::PNG));
    }

    /**
     * O arquivo falso do framework informa o tipo pelo nome. Aqui o tipo precisa
     * vir do conteúdo, como em um envio real.
     */
    private function upload(string $name, string $content): UploadedFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'avatar');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function acceptedImages(): array
    {
        return [
            'jpg' => ['foto.jpg', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9"],
            'png' => ['foto.png', (string) base64_decode(self::PNG)],
            'webp' => ['foto.webp', "RIFF\x24\x00\x00\x00WEBPVP8 \x18\x00\x00\x00\x30\x01\x00\x9D\x01\x2A\x01\x00\x01\x00\x01\x40\x25\xA4\x00\x03\x70\x00\xFE\xFB\x94\x00\x00"],
        ];
    }

    #[DataProvider('acceptedImages')]
    public function test_an_allowed_image_is_saved(string $name, string $content): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/avatar', ['avatar' => $this->upload($name, $content)])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        Storage::disk('local')->assertExists(UserData::firstWhere('user_id', $user->id)->avatar_path);
    }

    public function test_the_first_image_creates_the_record_with_the_account_name(): void
    {
        $user = User::factory()->create(['name' => 'Nome da Conta']);

        $this->actingAs($user)->post('/profile/avatar', ['avatar' => $this->png()]);

        $this->assertDatabaseHas('user_dados', ['user_id' => $user->id, 'full_name' => 'Nome da Conta']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function refusedFiles(): array
    {
        return [
            'texto com extensão de imagem' => ['foto.png', 'isto nao e uma imagem'],
            'php com extensão de imagem' => ['foto.jpg', '<?php echo 1;'],
            'svg' => ['foto.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'],
            'gif' => ['foto.gif', "GIF89a\x01\x00\x01\x00\x00\x00\x00;"],
        ];
    }

    #[DataProvider('refusedFiles')]
    public function test_a_file_that_is_not_an_allowed_image_is_refused(string $name, string $content): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/avatar', ['avatar' => $this->upload($name, $content)])
            ->assertSessionHasErrors('avatar');

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_file_above_the_limit_is_refused_with_the_limit_in_the_message(): void
    {
        config(['template.profile.avatar_max_kb' => 1024]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/avatar', ['avatar' => UploadedFile::fake()->create('foto.png', 1025, 'image/png')])
            ->assertSessionHasErrors('avatar');

        $this->assertStringContainsString('1 MB', session('errors')->first('avatar'));
    }

    public function test_the_request_needs_a_file(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/profile/avatar', ['avatar' => 'texto'])
            ->assertSessionHasErrors('avatar');
    }

    public function test_the_person_sees_the_own_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/avatar', ['avatar' => $this->png()]);

        $response = $this->get('/profile/avatar')->assertOk();

        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_the_image_is_not_available_to_visitors(): void
    {
        $this->get('/profile/avatar')->assertRedirect('/login');
    }

    public function test_an_account_without_image_gets_not_found(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile/avatar')->assertNotFound();
    }

    public function test_the_address_changes_when_the_image_changes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')
            ->assertInertia(fn (Assert $page) => $page->where('auth.avatarUrl', null));

        $this->post('/profile/avatar', ['avatar' => $this->png()]);
        $first = $this->get('/profile')->viewData('page')['props']['auth']['avatarUrl'];

        $this->post('/profile/avatar', ['avatar' => $this->png()]);
        $second = $this->get('/profile')->viewData('page')['props']['auth']['avatarUrl'];

        $this->assertStringContainsString('/profile/avatar', $first);
        $this->assertNotSame($first, $second);
    }

    public function test_replacing_keeps_the_previous_file(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/avatar', ['avatar' => $this->png()]);
        $previous = UserData::firstWhere('user_id', $user->id)->avatar_path;

        $this->post('/profile/avatar', ['avatar' => $this->png()]);

        Storage::disk('local')->assertExists($previous);
        $this->assertNotSame($previous, UserData::firstWhere('user_id', $user->id)->avatar_path);
    }

    public function test_removing_keeps_the_file_and_the_path_in_the_history(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/avatar', ['avatar' => $this->png()]);
        $path = UserData::firstWhere('user_id', $user->id)->avatar_path;

        $this->delete('/profile/avatar')->assertRedirect('/profile');

        $this->assertNull(UserData::firstWhere('user_id', $user->id)->avatar_path);
        Storage::disk('local')->assertExists($path);
        $this->assertTrue(ChangeHistory::where('table_name', 'user_dados')
            ->where('field', 'avatar_path')->where('old_value', $path)->exists());
    }

    public function test_removing_without_an_image_changes_nothing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/profile/avatar')->assertRedirect('/profile');

        $this->assertDatabaseMissing('user_dados', ['user_id' => $user->id]);
    }

    public function test_the_page_receives_the_size_limit(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertInertia(fn (Assert $page) => $page->where('avatarMaxKb', 2048));
    }
}
