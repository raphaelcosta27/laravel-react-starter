<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\User;

/**
 * Vale para quem não é administrador: administradores passam por Gate::before.
 */
class ProfilePolicy
{
    public function update(User $actor, Profile $profile): bool
    {
        return $actor->can('editar_perfil')
            && ! $profile->isAdministrator()
            && $actor->profile_id !== $profile->id;
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
