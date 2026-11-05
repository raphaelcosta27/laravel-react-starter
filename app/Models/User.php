<?php

namespace App\Models;

use App\Models\Concerns\RecordsChanges;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'profile_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, RecordsChanges, SoftDeletes;

    /**
     * Todo usuário nasce ativo e com a troca de senha pendente.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'must_change_password' => true,
        'is_active' => true,
    ];

    /**
     * Resultado da última operação, para quem chamou decidir como avisar.
     */
    public bool $wasReactivated = false;

    public bool $emailFailed = false;

    /**
     * Permissões efetivas, calculadas uma vez por instância, ou seja, por requisição.
     *
     * @var array<int, string>|null
     */
    private ?array $effectivePermissions = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'temporary_password_expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Indica se a senha atual é temporária e já passou da validade.
     */
    public function temporaryPasswordExpired(): bool
    {
        return $this->temporary_password_expires_at?->isPast() ?? false;
    }

    /**
     * Exige a troca de senha na próxima requisição do usuário.
     */
    public function requirePasswordChange(): void
    {
        $this->forceFill(['must_change_password' => true])->save();
    }

    /** @return BelongsTo<Profile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /** @return HasMany<UserPermission, $this> */
    public function permissionAdjustments(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function isAdministrator(): bool
    {
        return $this->profile?->isAdministrator() ?? false;
    }

    /**
     * Permissões do perfil somadas às concessões, menos as revogações. Vínculos,
     * permissões e perfis excluídos não têm efeito.
     *
     * @return array<int, string>
     */
    public function effectivePermissions(): array
    {
        return $this->effectivePermissions ??= $this->resolveEffectivePermissions();
    }

    /** @return array<int, string> */
    private function resolveEffectivePermissions(): array
    {
        $fromProfile = $this->profile
            ? Permission::whereIn('id', $this->profile->profilePermissions()->select('permission_id'))->pluck('code')
            : collect();

        $adjustments = $this->permissionAdjustments()->with('permission')->get()
            ->filter(fn (UserPermission $adjustment) => $adjustment->permission !== null);

        $codesWith = fn (string $effect) => $adjustments
            ->where('effect', $effect)
            ->map(fn (UserPermission $adjustment) => $adjustment->permission->code);

        return $fromProfile
            ->merge($codesWith(UserPermission::GRANT))
            ->diff($codesWith(UserPermission::REVOKE))
            ->unique()
            ->values()
            ->all();
    }
}
