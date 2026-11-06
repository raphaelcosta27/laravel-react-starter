<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\User;

/**
 * Vale para quem não é administrador: administradores passam por Gate::before.
 * Somente administradores agem sobre um administrador ou atribuem esse perfil.
 * Ninguém concede o que não possui: conta o que passaria a valer para o alvo.
 */
class UserPolicy
{
    public function create(User $actor, Profile $profile): bool
    {
        return $actor->can('cadastrar_usuario')
            && ! $profile->isAdministrator()
            && $actor->holdsPermissions($profile->permissionCodes());
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->can('editar_usuario') && ! $target->isAdministrator();
    }

    /** Trocar o e-mail abre caminho para assumir a conta: administradores decidem em Gate::before. */
    public function changeEmail(User $actor, User $target): bool
    {
        return false;
    }

    public function changeProfile(User $actor, User $target, Profile $profile): bool
    {
        return ! $profile->isAdministrator()
            && $actor->id !== $target->id
            && $actor->holdsPermissions(array_diff($profile->permissionCodes(), $target->effectivePermissions()));
    }

    public function changeStatus(User $actor, User $target): bool
    {
        return $actor->can('alterar_situacao_usuario') && ! $target->isAdministrator();
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->can('excluir_usuario') && ! $target->isAdministrator();
    }

    public function restore(User $actor, User $target): bool
    {
        return $actor->can('restaurar_usuario') && ! $target->isAdministrator();
    }

    public function managePermissions(User $actor, User $target): bool
    {
        return $actor->can('gerenciar_permissoes_usuario')
            && ! $target->isAdministrator()
            && $actor->id !== $target->id;
    }

    /** @param array<int, array{permission_id: int|string, effect: string}> $adjustments */
    public function adjustPermissions(User $actor, User $target, array $adjustments): bool
    {
        return $actor->holdsPermissions(array_diff($target->permissionsWith($adjustments), $target->effectivePermissions()));
    }

    public function requestPasswordChange(User $actor, User $target): bool
    {
        return $actor->can('solicitar_troca_senha') && ! $target->isAdministrator();
    }

    public function sendAuthenticationEmail(User $actor, User $target): bool
    {
        return $actor->can('enviar_email_autenticacao') && ! $target->isAdministrator();
    }
}
