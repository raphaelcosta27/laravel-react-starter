<?php

namespace Tests\Support;

use App\Support\Permissions\PackageModules;

/**
 * Módulo falso, como o que um pacote Composer registraria no próprio
 * ServiceProvider. Veja docs/especificacoes/modulos-de-pacotes.md.
 */
class FakePackageModule
{
    public const KEY = 'fake_module';

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function definition(array $overrides = []): array
    {
        return array_replace([
            'label' => 'Fake module',
            'view' => 'visualizar_fake',
            'without_records' => false,
            'basic' => ['visualizar_fake', 'cadastrar_fake', 'editar_fake', 'excluir_fake'],
            'specific' => ['publicar_fake'],
            'enabled_by' => null,
            'permissions' => [
                'visualizar_fake' => ['title' => 'Visualizar Fake', 'description' => 'Permite consultar o módulo falso.'],
                'cadastrar_fake' => ['title' => 'Cadastrar Fake', 'description' => 'Permite cadastrar no módulo falso.'],
                'editar_fake' => ['title' => 'Editar Fake', 'description' => 'Permite editar no módulo falso.'],
                'excluir_fake' => ['title' => 'Excluir Fake', 'description' => 'Permite excluir no módulo falso.'],
                'publicar_fake' => ['title' => 'Publicar Fake', 'description' => 'Permite publicar no módulo falso.'],
            ],
            'menu' => [
                'label' => 'Fake module',
                'route' => 'admin.fake',
                'index' => 'admin.fake.index',
                'icon' => 'article',
                'group' => 'config',
            ],
        ], $overrides);
    }

    /** @param  array<string, mixed>  $overrides */
    public static function register(array $overrides = []): void
    {
        PackageModules::register(self::KEY, self::definition($overrides));
    }
}
