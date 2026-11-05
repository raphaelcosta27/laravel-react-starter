<?php

namespace App\Models;

use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'description', 'system_key'])]
#[Hidden(['active_name'])]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory, SoftDeletes;

    public const ADMINISTRATOR = 'administrator';

    public const DEFAULT = 'default';

    /**
     * Perfil master. É sempre o primeiro a ser criado.
     */
    public static function administrator(): self
    {
        return static::firstOrCreate(
            ['system_key' => self::ADMINISTRATOR],
            ['name' => config('template.admin.admin_profile_name')],
        );
    }

    /**
     * Perfil usado quando a criação do usuário não permite escolher um.
     */
    public static function default(): self
    {
        static::administrator();

        return static::firstOrCreate(
            ['system_key' => self::DEFAULT],
            ['name' => config('template.admin.default_profile_name')],
        );
    }

    public function isAdministrator(): bool
    {
        return $this->system_key === self::ADMINISTRATOR;
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<ProfilePermission, $this> */
    public function profilePermissions(): HasMany
    {
        return $this->hasMany(ProfilePermission::class);
    }
}
