<?php

namespace App\Models;

use App\Models\Concerns\RecordsChanges;
use Database\Factories\UserContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * O valor fica sem máscara: só dígitos no telefone, minúsculas no e-mail.
 */
#[Fillable(['type', 'value', 'is_whatsapp', 'is_emergency', 'relationship', 'contact_name'])]
#[Hidden(['active_value'])]
class UserContact extends Model
{
    /** @use HasFactory<UserContactFactory> */
    use HasFactory, RecordsChanges, SoftDeletes;

    public const TYPE_PHONE = 'phone';

    public const TYPE_EMAIL = 'email';

    protected $table = 'user_contatos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_whatsapp' => 'boolean',
            'is_emergency' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
