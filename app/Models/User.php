<?php

namespace App\Models;

use App\Models\Concerns\RecordsChanges;
use App\Support\Permissions\Modules;
use Database\Factories\UserFactory;
use DateTimeInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'profile_id'])]
#[Hidden(['password', 'remember_token', 'personalData'])]
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

    /** @var array<int, string>|null */
    private ?array $assignedPermissions = null;

    /**
     * O nome completo repete o nome da conta. UserData faz o caminho inverso.
     */
    protected static function booted(): void
    {
        static::updated(function (User $user) {
            if ($user->wasChanged('name')) {
                UserData::withTrashed()->where('user_id', $user->id)->update(['full_name' => $user->name]);
            }
        });
    }

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
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Indica se a senha atual é temporária e já passou da validade.
     */
    /** Chave de tradução do motivo que impede o login, se houver. */
    public function loginRefusal(): ?string
    {
        return match (true) {
            ! $this->is_active => 'auth.inactive',
            $this->temporaryPasswordExpired() => 'auth.temporary_password_expired',
            default => null,
        };
    }

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

    /** @param  Builder<User>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<User>  $query */
    public function scopeInactive(Builder $query): void
    {
        $query->where('is_active', false);
    }

    /** @param  Builder<User>  $query */
    public function scopeConfirmationPending(Builder $query): void
    {
        $query->whereNull('email_verified_at');
    }

    /** @param  Builder<User>  $query */
    public function scopePasswordChangePending(Builder $query): void
    {
        $query->where('must_change_password', true);
    }

    /** @param  Builder<User>  $query */
    public function scopeWithExpiredTemporaryPassword(Builder $query): void
    {
        $query->where('temporary_password_expires_at', '<', now());
    }

    /** @param  Builder<User>  $query */
    public function scopeAccessedSince(Builder $query, DateTimeInterface $date): void
    {
        $query->where('last_login_at', '>=', $date);
    }

    /** @param  Builder<User>  $query */
    public function scopeWithoutRecordedAccess(Builder $query): void
    {
        $query->whereNull('last_login_at');
    }

    /** @return BelongsTo<Profile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /** @return HasOne<UserData, $this> */
    public function personalData(): HasOne
    {
        return $this->hasOne(UserData::class);
    }

    /** Como a pessoa quer ser chamada. */
    public function displayName(): string
    {
        return $this->personalData?->preferred_name ?: $this->name;
    }

    /** O endereço muda com a imagem, para o navegador não mostrar a anterior. */
    public function avatarUrl(): ?string
    {
        $path = $this->personalData?->avatar_path;

        return $path === null ? null : route('profile.avatar.show', ['v' => pathinfo($path, PATHINFO_FILENAME)]);
    }

    /** A foto vista por quem gerencia usuários, em uma rota com permissão própria. */
    public function managedAvatarUrl(): ?string
    {
        $path = $this->personalData?->avatar_path;

        return $path === null ? null : route('admin.users.avatar', ['user' => $this->id, 'v' => pathinfo($path, PATHINFO_FILENAME)]);
    }

    /** @return HasMany<ScreenNotification, $this> */
    public function screenNotifications(): HasMany
    {
        return $this->hasMany(ScreenNotification::class);
    }

    /** @return HasMany<UserAddress, $this> */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    /** @return HasMany<UserContact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(UserContact::class);
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
     * As atribuídas, sem as de módulos em que falta Visualizar.
     *
     * @return array<int, string>
     */
    public function effectivePermissions(): array
    {
        return $this->effectivePermissions ??= Modules::effective($this->assignedPermissions());
    }

    /**
     * Permissões do perfil somadas às concessões, menos as revogações. Vínculos,
     * permissões e perfis excluídos não têm efeito.
     *
     * @return array<int, string>
     */
    public function assignedPermissions(): array
    {
        return $this->assignedPermissions ??= $this->resolveAssignedPermissions();
    }

    /**
     * Administradores têm tudo, sem depender de vínculos.
     *
     * @param  array<int, string>  $codes
     */
    public function holdsPermissions(array $codes): bool
    {
        return $this->isAdministrator() || array_diff($codes, $this->effectivePermissions()) === [];
    }

    /**
     * Como ficariam as permissões efetivas com estes ajustes. Conceder Visualizar
     * ativa o que estava sem efeito, e isso também conta. Na repetição de
     * uma permissão, vale o último item, como em SyncUserPermissions.
     *
     * @param  array<int, array{permission_id: int|string, effect: string}>  $adjustments
     * @return array<int, string>
     */
    public function permissionsWith(array $adjustments): array
    {
        $effects = collect($adjustments)->mapWithKeys(
            fn (array $adjustment) => [(int) $adjustment['permission_id'] => $adjustment['effect']],
        );

        $codes = Permission::whereIn('id', $effects->keys())->pluck('code', 'id');

        return Modules::effective($this->combinePermissions(
            $this->profile?->permissionCodes() ?? [],
            $codes->filter(fn (string $code, int $id) => $effects[$id] === UserPermission::GRANT),
            $codes->filter(fn (string $code, int $id) => $effects[$id] === UserPermission::REVOKE),
        ));
    }

    /**
     * Como ficariam as permissões efetivas com este perfil, mantendo os ajustes.
     *
     * @return array<int, string>
     */
    public function permissionsWithProfile(Profile $profile): array
    {
        return Modules::effective($this->resolveAssignedPermissions($profile));
    }

    /** @return array<int, string> */
    private function resolveAssignedPermissions(?Profile $profile = null): array
    {
        $adjustments = $this->permissionAdjustments()->with('permission')->get()
            ->filter(fn (UserPermission $adjustment) => $adjustment->permission !== null);

        $codesWith = fn (string $effect) => $adjustments
            ->where('effect', $effect)
            ->map(fn (UserPermission $adjustment) => $adjustment->permission->code);

        return $this->combinePermissions(
            ($profile ?? $this->profile)?->permissionCodes() ?? [],
            $codesWith(UserPermission::GRANT),
            $codesWith(UserPermission::REVOKE),
        );
    }

    /**
     * @param  array<int, string>  $base
     * @param  Collection<int, string>  $granted
     * @param  Collection<int, string>  $revoked
     * @return array<int, string>
     */
    private function combinePermissions(array $base, Collection $granted, Collection $revoked): array
    {
        return collect($base)
            ->merge($granted)
            ->diff($revoked)
            ->unique()
            ->values()
            ->all();
    }
}
