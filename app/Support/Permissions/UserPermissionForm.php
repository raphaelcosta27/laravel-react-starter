<?php

namespace App\Support\Permissions;

use App\Models\Permission;
use App\Models\User;

class UserPermissionForm
{
    /**
     * O modal mostra o que está guardado, inclusive o que não tem efeito por
     * falta de Visualizar: é assim que a pessoa consegue corrigir.
     *
     * @return array<string, mixed>
     */
    public static function for(User $user, User $actor, string $origin): array
    {
        $inProfile = $user->profile?->permissionCodes() ?? [];
        $assigned = $user->assignedPermissions();

        return [
            'mode' => 'user-permissions',
            'user' => $user->only('id', 'name'),
            'canAdjust' => ! $user->isAdministrator(),
            'origin' => $origin,
            'modules' => Modules::groups(Permission::all(), fn (Permission $permission) => [
                ...$permission->only('id', 'title', 'code', 'description'),
                'inProfile' => in_array($permission->code, $inProfile, true),
                'assigned' => in_array($permission->code, $assigned, true),
                'available' => $actor->holdsPermissions([$permission->code]),
            ]),
        ];
    }
}
