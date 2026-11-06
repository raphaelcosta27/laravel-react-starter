<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    private function account(array $attributes = []): User
    {
        return User::factory()->create(['must_change_password' => false, ...$attributes]);
    }

    /** @return array<string, array{string}> */
    public static function themes(): array
    {
        return ['claro' => ['light'], 'escuro' => ['dark'], 'sistema' => ['system']];
    }

    #[DataProvider('themes')]
    public function test_guarda_a_escolha_na_conta(string $theme): void
    {
        $user = $this->account();

        $this->actingAs($user)
            ->patchJson(route('profile.theme.update'), ['theme' => $theme])
            ->assertOk()
            ->assertExactJson(['theme' => $theme]);

        $this->assertSame($theme, $user->fresh()->theme);
    }

    public function test_conta_nova_acompanha_o_sistema(): void
    {
        $this->assertSame('system', $this->account()->fresh()->theme);
    }

    public function test_recusa_valor_invalido(): void
    {
        $user = $this->account(['theme' => 'dark']);

        $this->actingAs($user)
            ->patchJson(route('profile.theme.update'), ['theme' => 'azul'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('theme');

        $this->assertSame('dark', $user->fresh()->theme);
    }

    public function test_exige_sessao(): void
    {
        $this->patchJson(route('profile.theme.update'), ['theme' => 'dark'])->assertUnauthorized();
    }

    public function test_o_cadastro_nao_altera_o_tema_em_massa(): void
    {
        $user = $this->account();

        $user->fill(['theme' => 'dark']);

        $this->assertNotSame('dark', $user->theme);
    }

    public function test_a_tela_recebe_a_escolha_da_conta(): void
    {
        $this->actingAs($this->account(['theme' => 'dark']))
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.theme', 'dark'));
    }

    public function test_sem_sessao_a_escolha_fica_com_o_aparelho(): void
    {
        $this->get(route('login'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.theme', null));
    }

    public function test_o_html_aplica_o_tema_antes_de_pintar_a_tela(): void
    {
        $this->actingAs($this->account(['theme' => 'dark']))
            ->get(route('dashboard'))
            ->assertSee('var theme = "dark";', false)
            ->assertSee("setAttribute('data-color-scheme', theme)", false);
    }

    public function test_a_marca_chega_as_telas(): void
    {
        config([
            'template.branding.tagline' => 'Tudo em um lugar',
            'template.branding.logo_dark' => '/images/logo-dark.svg',
        ]);

        $this->get(route('login'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('branding.tagline', 'Tudo em um lugar')
            ->where('branding.logoDark', '/images/logo-dark.svg'));
    }
}
