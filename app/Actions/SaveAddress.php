<?php

namespace App\Actions;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveAddress
{
    private const ADDRESS_FIELDS = [
        'postal_code', 'street', 'number', 'complement', 'reference', 'neighborhood', 'city', 'state', 'country',
    ];

    /**
     * Quem tem endereço tem exatamente um principal: o primeiro assume, e o
     * atual só deixa de ser quando outro é marcado. A trava na conta faz dois
     * envios simultâneos entrarem em fila.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data, ?UserAddress $address = null): void
    {
        DB::transaction(function () use ($user, $data, $address) {
            User::whereKey($user->id)->lockForUpdate()->first();

            $current = $user->addresses()->firstWhere('is_main', true);
            $keepsCurrent = $current !== null && $address !== null && $current->is($address);
            $main = $current === null || $keepsCurrent || (bool) ($data['is_main'] ?? false);

            if ($main && $current !== null && ! $keepsCurrent) {
                $current->update(['is_main' => false]);
            }

            $delivery = (bool) ($data['is_delivery'] ?? false);

            $attributes = [
                ...array_fill_keys(self::ADDRESS_FIELDS, null),
                ...Arr::only($data, self::ADDRESS_FIELDS),
                'is_main' => $main,
                'is_billing' => (bool) ($data['is_billing'] ?? false),
                'is_delivery' => $delivery,
                'recipient_name' => $delivery ? $data['recipient_name'] : null,
            ];

            $address === null ? $user->addresses()->create($attributes) : $address->update($attributes);
        });
    }
}
