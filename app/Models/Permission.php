<?php

namespace App\Models;

use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

#[Fillable(['title', 'code', 'description'])]
#[Hidden(['active_code'])]
class Permission extends Model
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * O código é usado dentro do sistema e não muda depois de criado.
     */
    protected static function booted(): void
    {
        static::updating(function (self $permission) {
            if ($permission->isDirty('code')) {
                throw new LogicException('O código de uma permissão não pode ser alterado.');
            }
        });
    }

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    /** @return HasMany<ProfilePermission, $this> */
    public function profilePermissions(): HasMany
    {
        return $this->hasMany(ProfilePermission::class);
    }

    /** @return HasMany<UserPermission, $this> */
    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }
}
