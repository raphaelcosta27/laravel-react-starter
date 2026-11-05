<?php

namespace App\Models;

use App\Models\Concerns\RecordsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ajuste individual: concede ou revoga uma permissão somente para aquele usuário.
 */
#[Fillable(['user_id', 'permission_id', 'effect'])]
class UserPermission extends Model
{
    use RecordsChanges, SoftDeletes;

    public const GRANT = 'grant';

    public const REVOKE = 'revoke';

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Permission, $this> */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
