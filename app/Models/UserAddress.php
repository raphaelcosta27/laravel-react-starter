<?php

namespace App\Models;

use App\Models\Concerns\RecordsChanges;
use Database\Factories\UserAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'postal_code', 'street', 'number', 'complement', 'reference', 'neighborhood', 'city', 'state', 'country',
    'is_main', 'is_billing', 'is_delivery', 'recipient_name',
])]
#[Hidden(['active_main'])]
class UserAddress extends Model
{
    /** @use HasFactory<UserAddressFactory> */
    use HasFactory, RecordsChanges, SoftDeletes;

    protected $table = 'user_enderecos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'is_billing' => 'boolean',
            'is_delivery' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
