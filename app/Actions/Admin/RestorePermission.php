<?php

namespace App\Actions\Admin;

use App\Models\Permission;
use Illuminate\Validation\ValidationException;

class RestorePermission
{
    /**
     * Os vínculos ficam intactos na exclusão, então voltam junto com a permissão.
     *
     * @throws ValidationException
     */
    public function handle(Permission $permission): void
    {
        if (Permission::where('code', $permission->code)->exists()) {
            throw ValidationException::withMessages([
                'permission' => __('Another permission already uses this code.'),
            ]);
        }

        $permission->restore();
    }
}
