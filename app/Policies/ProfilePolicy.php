<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use App\Support\Permissions\Modules;

/**
 * Vale para quem não é administrador: administradores passam por Gate::before.
 * Ninguém adiciona a um perfil o que não possui, mas pode manter o que já existe.
 * Conta o que passaria a valer: conceder Visualizar ativa o restante do módulo.
 */
class ProfilePolicy
{
    public function update(User $actor, Profile $profile): bool
    {
        return $actor->can('editar_perfil')
            && ! $profile->isAdministrator()
            && $actor->profile_id !== $profile->id;
    }

    /** @param array<int, int|string> $permissionIds */
    public function setPermissions(User $actor, Profile $profile, array $permissionIds): bool
    {
        return $actor->holdsPermissions(array_diff(
            Modules::effective(Permission::whereIn('id', $permissionIds)->pluck('code')->all()),
            Modules::effective($profile->permissionCodes()),
        ));
    }

    public function delete(User $actor, Profile $profile): bool
    {
        return $actor->can('excluir_perfil') && ! $profile->isAdministrator();
    }

    public function restore(User $actor, Profile $profile): bool
    {
        return $actor->can('restaurar_perfil') && ! $profile->isAdministrator();
    }
}
