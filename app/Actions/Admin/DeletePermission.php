<?php

namespace App\Actions\Admin;

use App\Models\Permission;
use Illuminate\Validation\ValidationException;

class DeletePermission
{
    /**
     * Usuários excluídos e vínculos excluídos não contam: as relações já os ignoram.
     *
     * @throws ValidationException
     */
    public function handle(Permission $permission): void
    {
        if ($permission->is_system) {
            throw ValidationException::withMessages([
                'permission' => __('Initial panel permissions cannot be deleted.'),
            ]);
        }

        if ($this->isLinkedToUsers($permission)) {
            throw ValidationException::withMessages([
                'permission' => __('This permission is linked to users and cannot be deleted.'),
            ]);
        }

        $permission->delete();
    }

    private function isLinkedToUsers(Permission $permission): bool
    {
        return $permission->profilePermissions()->whereHas('profile.users')->exists()
            || $permission->userPermissions()->whereHas('user')->exists();
    }
}
