<?php

namespace App\Actions\Admin;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncUserPermissions
{
    /**
     * Sem `sync`: remover exclui logicamente, e refazer restaura a mesma linha.
     * Ajuste de permissão excluída não aparece na tela e fica como está.
     *
     * @param  array<int, array{permission_id: int|string, effect: string}>  $adjustments
     *
     * @throws ValidationException
     */
    public function handle(User $user, array $adjustments): void
    {
        if ($user->isAdministrator()) {
            throw ValidationException::withMessages([
                'user' => __('Administrators have every permission and do not accept individual adjustments.'),
            ]);
        }

        $wanted = collect($adjustments)->mapWithKeys(
            fn (array $adjustment) => [(int) $adjustment['permission_id'] => $adjustment['effect']],
        );

        DB::transaction(function () use ($user, $wanted) {
            $rows = UserPermission::withTrashed()->with('permission')->where('user_id', $user->id)->get()->keyBy('permission_id');

            foreach ($rows as $permissionId => $row) {
                if (! $wanted->has($permissionId) && ! $row->trashed() && $row->permission !== null) {
                    $row->delete();
                }
            }

            foreach ($wanted as $permissionId => $effect) {
                $row = $rows->get($permissionId);

                if ($row === null) {
                    UserPermission::create([
                        'user_id' => $user->id,
                        'permission_id' => $permissionId,
                        'effect' => $effect,
                    ]);

                    continue;
                }

                $row->effect = $effect;
                $row->deleted_at = null;
                $row->save();
            }
        });
    }
}
