<?php

namespace App\Actions;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteAddress
{
    /**
     * Excluir o principal exige escolher o próximo entre os demais da pessoa.
     *
     * @throws ValidationException
     */
    public function handle(User $user, UserAddress $address, mixed $newMainId = null): void
    {
        DB::transaction(function () use ($user, $address, $newMainId) {
            User::whereKey($user->id)->lockForUpdate()->first();

            $others = $user->addresses()->whereKeyNot($address->id)->get();
            $newMain = is_numeric($newMainId) ? $others->find((int) $newMainId) : null;

            if ($address->fresh()->is_main && $others->isNotEmpty() && $newMain === null) {
                throw ValidationException::withMessages(['new_main_id' => __('Choose the new main address.')]);
            }

            $address->delete();

            if ($address->is_main) {
                $newMain?->update(['is_main' => true]);
            }
        });
    }
}
