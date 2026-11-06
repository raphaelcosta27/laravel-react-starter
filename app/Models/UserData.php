<?php

namespace App\Models;

use App\Models\Concerns\RecordsChanges;
use Database\Factories\UserDataFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Extensão 1:1 do usuário. O CPF fica oculto: o histórico o mascara e nenhuma
 * serialização o expõe. Quem precisa dele lê o atributo diretamente.
 */
#[Fillable(['full_name', 'preferred_name', 'birth_date', 'gender', 'cpf', 'avatar_path'])]
#[Hidden(['cpf', 'active_cpf'])]
class UserData extends Model
{
    /** @use HasFactory<UserDataFactory> */
    use HasFactory, RecordsChanges, SoftDeletes;

    protected $table = 'user_dados';

    /**
     * O nome completo repete o nome da conta. User faz o caminho inverso.
     */
    protected static function booted(): void
    {
        static::saving(function (UserData $data) {
            $data->full_name ??= $data->user->name;
        });

        static::saved(function (UserData $data) {
            if ($data->user->name !== $data->full_name) {
                $data->user->forceFill(['name' => $data->full_name])->save();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
