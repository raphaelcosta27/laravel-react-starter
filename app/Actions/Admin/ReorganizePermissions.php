<?php

namespace App\Actions\Admin;

use App\Models\Permission;
use App\Models\ProfilePermission;
use App\Models\User;
use App\Models\UserPermission;
use App\Support\Permissions\Modules;
use Illuminate\Support\Facades\DB;

/**
 * Cria o catálogo de permissões e leva quem já usa o sistema às permissões por
 * módulo. Cada passo só roda uma vez, para não desfazer o que veio depois.
 */
class ReorganizePermissions
{
    private const OLD_TITLES = [
        'cadastrar_perfil' => 'Cadastrar Perfis',
        'cadastrar_permissao' => 'Cadastrar Permissões',
        'cadastrar_usuario' => 'Cadastrar Usuários',
        'configura_env' => 'Configurar Ambiente',
    ];

    private const EXPORTS = ['exportar_perfil', 'exportar_permissao', 'exportar_usuario'];

    public function handle(): void
    {
        DB::transaction(function () {
            $managerMoved = $this->isOutsideItsModule('gerenciar_permissoes_usuario');
            $created = $this->createCatalog();

            $this->assignModules();
            $this->renameTitles();

            if (in_array('visualizar_ambiente', $created, true)) {
                $this->spread('configura_env', 'visualizar_ambiente');
            }

            if ($managerMoved) {
                $this->spread('gerenciar_permissoes_usuario', 'visualizar_permissao');
            }

            $this->replaceGlobalExport();
        });
    }

    private function isOutsideItsModule(string $code): bool
    {
        return Permission::where('code', $code)->whereNull('module')->exists();
    }

    /**
     * Procura também em registros excluídos, para não duplicar.
     *
     * @return array<int, string>
     */
    private function createCatalog(): array
    {
        $created = [];

        foreach (trans('permissions', [], 'pt_BR') as $code => $texts) {
            if (Permission::withTrashed()->where('code', $code)->exists()) {
                continue;
            }

            $permission = new Permission(['code' => $code, ...$texts]);
            $permission->is_system = true;
            $permission->save();

            $created[] = $code;
        }

        return $created;
    }

    private function assignModules(): void
    {
        foreach (Modules::catalog() as $code => $module) {
            Permission::withTrashed()->where('code', $code)->toBase()->update(['module' => $module]);
        }
    }

    /** Um título editado pela pessoa fica como está. */
    private function renameTitles(): void
    {
        foreach (self::OLD_TITLES as $code => $title) {
            Permission::where('code', $code)->where('title', $title)->toBase()
                ->update(['title' => trans("permissions.{$code}.title", [], 'pt_BR')]);
        }
    }

    private function replaceGlobalExport(): void
    {
        $global = Permission::firstWhere('code', 'exportar_dados');

        if ($global === null) {
            return;
        }

        foreach (self::EXPORTS as $code) {
            $this->spread('exportar_dados', $code);
        }

        $global->delete();
    }

    /**
     * Quem tem a origem passa a ter o destino, sem mudar quem fica sem: a revogação
     * da origem acompanha o destino que chegou pelo perfil.
     */
    private function spread(string $source, string $target): void
    {
        $from = Permission::firstWhere('code', $source);
        $to = Permission::firstWhere('code', $target);

        if ($from === null || $to === null) {
            return;
        }

        $reached = [];

        foreach (ProfilePermission::where('permission_id', $from->id)->pluck('profile_id') as $profileId) {
            $link = ProfilePermission::withTrashed()->firstOrNew(['profile_id' => $profileId, 'permission_id' => $to->id]);

            if ($link->exists && ! $link->trashed()) {
                continue;
            }

            $link->deleted_at = null;
            $link->save();

            $reached[] = $profileId;
        }

        foreach (UserPermission::where('permission_id', $from->id)->get() as $adjustment) {
            if (UserPermission::where('user_id', $adjustment->user_id)->where('permission_id', $to->id)->exists()) {
                continue;
            }

            $user = User::withTrashed()->find($adjustment->user_id);
            $viaProfile = in_array($user?->profile_id, $reached, true);

            if ($adjustment->effect === UserPermission::REVOKE && ! $viaProfile) {
                continue;
            }

            if ($adjustment->effect === UserPermission::GRANT && in_array($target, $user?->profile?->permissionCodes() ?? [], true)) {
                continue;
            }

            $row = UserPermission::withTrashed()->firstOrNew(['user_id' => $adjustment->user_id, 'permission_id' => $to->id]);
            $row->effect = $adjustment->effect;
            $row->deleted_at = null;
            $row->save();
        }
    }
}
