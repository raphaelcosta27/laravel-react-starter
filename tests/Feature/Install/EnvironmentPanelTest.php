<?php

namespace Tests\Feature\Install;

use App\Models\ChangeHistory;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use App\Support\Environment\EnvCatalog;
use App\Support\Environment\EnvFile;
use Dotenv\Dotenv;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;

class EnvironmentPanelTest extends EnvironmentTestCase
{
    private const SECRET = 'segredo-que-nao-sai';

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->env("# Nota do projeto\nAPP_NAME=Template\nAPP_KEY=base64:chave-real\nAPP_DEBUG=true\nAPP_URL=http://localhost\nDB_PASSWORD=".self::SECRET."\nMAIL_PORT=2525\nCUSTOM_THING=interno\n");

        $this->administrator = User::factory()->administrator()->create();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function payload(array $values): array
    {
        return ['values' => $values, 'version' => app(EnvFile::class)->version()];
    }

    private function save(array $values)
    {
        return $this->actingAs($this->administrator)->patch('/admin/environment', $this->payload($values));
    }

    private function parameter(string $key): array
    {
        return collect(app(EnvCatalog::class)->groups())->flatMap(fn (array $group) => $group['parameters'])->firstWhere('key', $key);
    }

    public function test_an_administrator_sees_the_parameters_grouped_and_described(): void
    {
        $this->actingAs($this->administrator)->get('/admin/environment')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Environment/Index')
                ->where('groups.0.key', 'application')
                ->where('groups.0.label', 'Aplicação')
                ->where('groups.0.parameters.0.key', 'APP_NAME')
                ->where('groups.0.parameters.0.value', 'Template')
                ->where('groups.0.parameters.0.description', 'Nome exibido nas telas e nos e-mails.')
                ->where('editable', true)
                ->where('writable', true));
    }

    public function test_every_documented_parameter_has_a_description_and_a_group(): void
    {
        $keys = array_keys(Dotenv::parse((string) file_get_contents(base_path('.env.example'))));
        $parameters = collect(app(EnvCatalog::class)->groups())->flatMap(fn (array $group) => $group['parameters'])->keyBy('key');

        foreach ($keys as $key) {
            $this->assertNotSame(trans('environment.custom', [], 'pt_BR'), $parameters[$key]['description'], "{$key} sem descrição.");
            $this->assertNotSame('other', $parameters[$key]['group'], "{$key} sem grupo.");
        }
    }

    public function test_the_file_parameters_outside_the_example_are_listed_without_value(): void
    {
        $parameter = $this->parameter('CUSTOM_THING');

        $this->assertSame('other', $parameter['group']);
        $this->assertNull($parameter['value']);
        $this->assertTrue($parameter['has_value']);
    }

    public function test_a_name_with_password_is_not_always_a_secret(): void
    {
        $this->assertFalse($this->parameter('PASSWORD_MIN_LENGTH')['secret']);
        $this->assertFalse($this->parameter('TEMPORARY_PASSWORD_EXPIRE')['secret']);
        $this->assertTrue($this->parameter('MAIL_PASSWORD')['secret']);
        $this->assertTrue($this->parameter('INITIAL_USER_PASSWORD')['secret']);
        $this->assertTrue($this->parameter('TURNSTILE_SECRET_KEY')['secret']);
        $this->assertFalse($this->parameter('TURNSTILE_SITE_KEY')['secret']);
    }

    public function test_the_page_as_it_opens_can_be_saved(): void
    {
        $values = collect(app(EnvCatalog::class)->groups())
            ->flatMap(fn (array $group) => $group['parameters'])
            ->reject(fn (array $parameter) => $parameter['locked'])
            ->mapWithKeys(fn (array $parameter) => [$parameter['key'] => $parameter['value'] ?? ''])
            ->all();

        $this->save($values)->assertSessionHasNoErrors();
    }

    public function test_no_secret_reaches_the_browser(): void
    {
        $content = (string) $this->actingAs($this->administrator)->get('/admin/environment')->getContent();

        $this->assertStringNotContainsString(self::SECRET, $content);
        $this->assertStringNotContainsString('chave-real', $content);
        $this->assertStringNotContainsString('interno', $content);
    }

    public function test_a_person_without_permission_is_refused(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/environment')->assertForbidden();
        $this->patch('/admin/environment', $this->payload(['APP_NAME' => 'X']))->assertForbidden();

        $this->assertStringContainsString('APP_NAME=Template', $this->envContents());
    }

    public function test_a_profile_with_the_permission_is_allowed(): void
    {
        $permission = Permission::factory()->create(['code' => 'visualizar_ambiente']);
        $profile = Profile::factory()->create();
        $profile->profilePermissions()->create(['permission_id' => $permission->id]);

        $this->actingAs(User::factory()->withProfile($profile)->create())
            ->get('/admin/environment')
            ->assertOk();
    }

    public function test_the_permission_is_seeded_and_warns_about_the_trust(): void
    {
        $this->assertStringContainsString('confiança', trans('permissions.configura_env.description', [], 'pt_BR'));
    }

    public function test_saving_updates_the_file_and_keeps_the_rest(): void
    {
        $this->save(['APP_NAME' => 'Meu Sistema', 'MAIL_PORT' => '587', 'APP_DEBUG' => false])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/environment');

        $contents = $this->envContents();

        $this->assertStringContainsString('APP_NAME="Meu Sistema"', $contents);
        $this->assertStringContainsString('MAIL_PORT=587', $contents);
        $this->assertStringContainsString('APP_DEBUG=false', $contents);
        $this->assertStringContainsString('# Nota do projeto', $contents);
        $this->assertStringContainsString('CUSTOM_THING=interno', $contents);
    }

    public function test_changing_the_name_keeps_everyone_logged_in(): void
    {
        $cookie = config('session.cookie');

        $this->save(['APP_NAME' => 'Novo'])->assertSessionHasNoErrors();

        $this->assertStringContainsString("SESSION_COOKIE={$cookie}", $this->envContents());
    }

    public function test_a_blank_secret_keeps_the_current_value(): void
    {
        $this->save(['DB_PASSWORD' => '', 'APP_NAME' => 'Novo'])->assertSessionHasNoErrors();

        $this->assertStringContainsString('DB_PASSWORD='.self::SECRET, $this->envContents());
    }

    public function test_the_application_key_cannot_be_changed(): void
    {
        $this->save(['APP_KEY' => 'base64:outra'])->assertSessionHasErrors('values.APP_KEY');

        $this->assertStringContainsString('APP_KEY=base64:chave-real', $this->envContents());
    }

    public function test_an_invalid_value_saves_nothing(): void
    {
        $this->save(['APP_NAME' => 'Novo', 'MAIL_PORT' => '99999'])->assertSessionHasErrors('values.MAIL_PORT');

        $this->assertStringContainsString('APP_NAME=Template', $this->envContents());
    }

    public function test_values_are_checked_by_type(): void
    {
        $this->save([
            'APP_DEBUG' => 'talvez',
            'MAIL_PORT' => 'abc',
            'APP_URL' => 'não é endereço',
            'APP_NAME' => "duas\nlinhas",
            'NAO_EXISTE' => '1',
        ])->assertSessionHasErrors([
            'values.APP_DEBUG',
            'values.MAIL_PORT',
            'values.APP_URL',
            'values.APP_NAME',
            'values.NAO_EXISTE',
        ]);
    }

    public function test_a_line_break_cannot_inject_another_parameter(): void
    {
        $this->save(['APP_NAME' => "X\nAPP_DEBUG=true"])->assertSessionHasErrors('values.APP_NAME');
    }

    public function test_a_second_save_with_the_old_page_is_warned(): void
    {
        $this->actingAs($this->administrator);
        $payload = $this->payload(['APP_NAME' => 'Primeiro']);

        $this->patch('/admin/environment', $payload)->assertSessionHasNoErrors();

        $this->patch('/admin/environment', ['values' => ['APP_NAME' => 'Segundo'], 'version' => $payload['version']])
            ->assertSessionHasErrors('environment');

        $this->assertStringContainsString('APP_NAME=Primeiro', $this->envContents());
    }

    public function test_a_database_change_that_does_not_connect_is_refused_without_the_password(): void
    {
        $response = $this->save(['DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '1', 'DB_PASSWORD' => 'senha-digitada']);

        $response->assertSessionHasErrors('values.DB_HOST');

        $this->assertStringNotContainsString('senha-digitada', session('errors')->first('values.DB_HOST'));
        $this->assertStringNotContainsString('DB_HOST=127.0.0.1', $this->envContents());
    }

    public function test_an_unknown_database_type_is_refused(): void
    {
        $this->save(['DB_CONNECTION' => 'oracle'])->assertSessionHasErrors('values.DB_HOST');
    }

    public function test_the_history_records_who_changed_without_secret_values(): void
    {
        $this->env("DB_CONNECTION=sqlite\nDB_DATABASE=:memory:\nAPP_NAME=Antigo\nTURNSTILE_SECRET_KEY=velha\n");

        $this->save(['APP_NAME' => 'Novo', 'TURNSTILE_SECRET_KEY' => 'nova-chave'])->assertSessionHasNoErrors();

        $rows = ChangeHistory::where('table_name', 'env')->get();

        $this->assertSame('Antigo', $rows->firstWhere('field', 'APP_NAME')->old_value);
        $this->assertSame('Novo', $rows->firstWhere('field', 'APP_NAME')->new_value);
        $this->assertSame($this->administrator->id, $rows->firstWhere('field', 'APP_NAME')->user_id);
        $this->assertSame('********', $rows->firstWhere('field', 'TURNSTILE_SECRET_KEY')->new_value);
        $this->assertStringNotContainsString('nova-chave', $rows->toJson());
        $this->assertStringNotContainsString('velha', $rows->toJson());
    }

    public function test_a_secret_never_reaches_the_log(): void
    {
        Log::spy();

        $this->save(['TURNSTILE_SECRET_KEY' => 'nova-chave']);

        Log::shouldNotHaveReceived('info');
        Log::shouldNotHaveReceived('error');
    }

    public function test_saving_can_be_turned_off(): void
    {
        config(['template.install.editor_enabled' => false]);

        $this->actingAs($this->administrator)->get('/admin/environment')
            ->assertInertia(fn (Assert $page) => $page->where('editable', false));

        $this->patch('/admin/environment', $this->payload(['APP_NAME' => 'X']))->assertForbidden();
    }

    public function test_a_value_defined_by_the_server_is_indicated(): void
    {
        $_SERVER['APP_NAME'] = 'Do Servidor';

        try {
            $parameter = $this->parameter('APP_NAME');
        } finally {
            unset($_SERVER['APP_NAME']);
        }

        $this->assertTrue($parameter['overridden']);
        $this->assertSame('Do Servidor', $parameter['effective']);
    }

    public function test_an_unwritable_file_shows_the_warning_and_loses_nothing(): void
    {
        $file = $this->partialMock(EnvFile::class, fn ($mock) => $mock->shouldReceive('writable')->andReturn(false));

        $this->assertFalse($file->writable());

        $this->save(['APP_NAME' => 'Novo'])->assertSessionHasErrors('environment');

        $this->assertStringContainsString('APP_NAME=Template', $this->envContents());
    }

    public function test_the_page_needs_the_file_to_exist_only_as_a_warning(): void
    {
        File::delete($this->envPath());

        $this->actingAs($this->administrator)->get('/admin/environment')->assertOk();
    }
}
