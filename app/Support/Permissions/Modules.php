<?php

namespace App\Support\Permissions;

use App\Models\Permission;
use Illuminate\Support\Collection;

/**
 * Áreas do sistema, declaradas em `template.modules`. Sem Visualizar, as demais
 * permissões de um módulo ficam guardadas, mas não têm efeito.
 */
class Modules
{
    public const OTHER = 'other';

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return config('template.modules', []);
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function view(?string $module): ?string
    {
        return self::all()[$module]['view'] ?? null;
    }

    /** @return array<int, string> */
    public static function codes(string $module): array
    {
        $definition = self::all()[$module] ?? [];

        return [...($definition['basic'] ?? []), ...($definition['specific'] ?? [])];
    }

    /**
     * @return array<string, string> Código da permissão e chave do módulo.
     */
    public static function catalog(): array
    {
        return collect(self::keys())
            ->flatMap(fn (string $module) => array_fill_keys(self::codes($module), $module))
            ->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return collect(self::all())
            ->map(fn (array $module, string $key) => ['value' => $key, 'label' => __($module['label'])])
            ->values()
            ->all();
    }

    public static function label(?string $module): string
    {
        return __(self::all()[$module]['label'] ?? 'Other permissions');
    }

    /**
     * Retira as permissões cujo módulo não tem Visualizar entre os códigos.
     *
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    public static function effective(array $codes): array
    {
        $modules = Permission::whereIn('code', $codes)->pluck('module', 'code');

        return array_values(array_filter($codes, function (string $code) use ($codes, $modules) {
            $view = self::view($modules[$code] ?? null);

            return $view === null || in_array($view, $codes, true);
        }));
    }

    /**
     * Permissões agrupadas para as telas, na ordem dos módulos. Dentro do módulo,
     * vêm as declaradas e depois as criadas pela tela.
     *
     * @param  Collection<int, Permission>  $permissions
     * @param  callable(Permission): array<string, mixed>  $present
     * @return array<int, array<string, mixed>>
     */
    public static function groups(Collection $permissions, callable $present): array
    {
        $byModule = $permissions->groupBy(
            fn (Permission $permission) => isset(self::all()[$permission->module]) ? $permission->module : self::OTHER,
        );

        return collect([...self::keys(), self::OTHER])
            ->filter(fn (string $module) => $byModule->has($module))
            ->map(function (string $module) use ($byModule, $present) {
                $declared = array_flip(self::codes($module));
                $sorted = $byModule[$module]->sortBy(
                    fn (Permission $permission) => [$declared[$permission->code] ?? PHP_INT_MAX, $permission->title],
                );
                $view = self::view($module);

                return [
                    'key' => $module,
                    'label' => self::label($module),
                    'view' => $sorted->firstWhere('code', $view)?->id,
                    'notice' => self::notice($module),
                    'permissions' => $sorted->map($present)->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private static function notice(string $module): ?string
    {
        $setting = self::all()[$module]['enabled_by'] ?? null;

        return $setting !== null && ! config($setting)
            ? __('This module is turned off by configuration.')
            : null;
    }
}
