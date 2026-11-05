<?php

namespace App\Actions\Admin;

use App\Models\Profile;
use App\Models\ProfilePermission;
use Illuminate\Support\Facades\DB;

class SyncProfilePermissions
{
    /**
     * Sem `sync`: remover exclui logicamente, e refazer restaura a mesma linha.
     * O perfil master tem tudo sem vínculos, que só bloqueariam exclusões.
     *
     * @param  array<int, int|string>  $permissionIds
     */
    public function handle(Profile $profile, array $permissionIds): void
    {
        if ($profile->isAdministrator()) {
            return;
        }

        $wanted = array_map('intval', $permissionIds);

        DB::transaction(function () use ($profile, $wanted) {
            $links = ProfilePermission::withTrashed()
                ->where('profile_id', $profile->id)
                ->get()
                ->keyBy('permission_id');

            foreach ($links as $permissionId => $link) {
                if (! in_array($permissionId, $wanted, true) && ! $link->trashed()) {
                    $link->delete();
                }
            }

            foreach ($wanted as $permissionId) {
                $link = $links->get($permissionId);

                if ($link === null) {
                    ProfilePermission::create(['profile_id' => $profile->id, 'permission_id' => $permissionId]);
                } elseif ($link->trashed()) {
                    $link->restore();
                }
            }
        });
    }
}
