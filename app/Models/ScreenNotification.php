<?php

namespace App\Models;

use App\Models\Concerns\RecordsChanges;
use Database\Factories\ScreenNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Notificação exibida no sino. O nome evita `notifications`, do Notifiable.
 */
#[Fillable(['title', 'body', 'type', 'link'])]
class ScreenNotification extends Model
{
    /** @use HasFactory<ScreenNotificationFactory> */
    use HasFactory, RecordsChanges, SoftDeletes;

    public const TYPES = ['info', 'success', 'warning', 'error'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seen_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
