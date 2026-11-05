<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Todo usuário nasce com a troca de senha pendente.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'must_change_password' => true,
    ];

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
}
