<?php

namespace App\Support\Permissions;

use InvalidArgumentException;

/**
 * Módulos declarados por pacotes Composer, no boot() do ServiceProvider de
 * cada um. Ficam numa instância do container, e não em config(), para valer com
 * config:cache e em qualquer ordem de providers. Veja
 * docs/especificacoes/modulos-de-pacotes.md.
 */
class PackageModules
{
    public const CONFIG_GROUP = 'config';

    private const MENU_FIELDS = ['label', 'route', 'index', 'icon'];

    /** @var array<string, array<string, mixed>> */
    private array $modules = [];

    /** @param  array<string, mixed>  $definition */
    public static function register(string $key, array $definition): void
    {
        $registry = self::instance();
        $registry->validate($key, $definition);
        $registry->modules[$key] = $definition;
    }

    /**
     * Definições no mesmo formato de `template.modules`, na ordem de registro.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return array_map(
            fn (array $definition) => array_diff_key($definition, ['permissions' => true, 'menu' => true]),
            self::instance()->modules,
        );
    }

    /**
     * Título e descrição de cada código declarado pelos pacotes.
     *
     * @return array<string, array{title: string, description: string}>
     */
    public static function permissions(): array
    {
        return array_merge(...array_values(array_map(
            fn (array $definition) => array_intersect_key($definition['permissions'], array_flip(self::declaredCodes($definition))),
            self::instance()->modules,
        )));
    }

    /**
     * Itens de menu, como as telas os recebem.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function menus(): array
    {
        return collect(self::instance()->modules)
            ->filter(fn (array $definition) => ! empty($definition['menu']))
            ->map(fn (array $definition, string $key) => [
                'key' => $key,
                ...array_intersect_key($definition['menu'], array_flip(self::MENU_FIELDS)),
                'group' => $definition['menu']['group'] ?? null,
                'permission' => $definition['view'],
            ])
            ->values()
            ->all();
    }

    public static function flush(): void
    {
        self::instance()->modules = [];
    }

    private static function instance(): self
    {
        return app(self::class);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<int, string>
     */
    private static function declaredCodes(array $definition): array
    {
        return [...($definition['basic'] ?? []), ...($definition['specific'] ?? [])];
    }

    /** @param  array<string, mixed>  $definition */
    private function validate(string $key, array $definition): void
    {
        $fail = fn (string $problem) => throw new InvalidArgumentException("Módulo de pacote [{$key}]: {$problem}.");
        $template = config('template.modules', []);

        if (isset($template[$key]) || isset($this->modules[$key])) {
            $fail('a chave já está em uso');
        }

        if (! is_string($definition['label'] ?? null) || $definition['label'] === '') {
            $fail('falta o label');
        }

        if (! is_array($definition['basic'] ?? null) || $definition['basic'] === []) {
            $fail('falta a lista basic');
        }

        if (! in_array($definition['view'] ?? null, $definition['basic'], true)) {
            $fail('a permissão view precisa estar em basic');
        }

        $taken = [
            ...collect($template)->flatMap(fn (array $module) => self::declaredCodes($module))->all(),
            ...collect($this->modules)->flatMap(fn (array $module) => self::declaredCodes($module))->all(),
        ];

        foreach (self::declaredCodes($definition) as $code) {
            $texts = $definition['permissions'][$code] ?? null;

            if (! is_string($texts['title'] ?? null) || ! is_string($texts['description'] ?? null)) {
                $fail("a permissão {$code} precisa de title e description em permissions");
            }

            if (in_array($code, $taken, true)) {
                $fail("a permissão {$code} já pertence a outro módulo");
            }
        }

        $this->validateMenu($definition['menu'] ?? null, $fail);
    }

    private function validateMenu(mixed $menu, callable $fail): void
    {
        if ($menu === null) {
            return;
        }

        foreach (self::MENU_FIELDS as $field) {
            if (! is_string($menu[$field] ?? null) || $menu[$field] === '') {
                $fail("falta {$field} no menu");
            }
        }

        if (! in_array($menu['group'] ?? null, [null, self::CONFIG_GROUP], true)) {
            $fail("o group do menu precisa ser '".self::CONFIG_GROUP."' ou nulo");
        }
    }
}
