<?php

namespace App\Support\Dashboard\Blocks;

use App\Models\ScreenNotification;
use App\Models\User;
use App\Support\Dashboard\Block;
use App\Support\ScreenNotifications;

/**
 * Só consulta: quem marca como vista é a barra lateral do sino.
 */
class Notifications implements Block
{
    private const LIMIT = 5;

    public function key(): string
    {
        return 'Notifications';
    }

    public function title(): string
    {
        return __('Notifications');
    }

    public function permission(): ?string
    {
        return null;
    }

    public function available(): bool
    {
        return (bool) config('template.notifications.enabled');
    }

    public function data(User $user): array
    {
        return [
            'unread' => ScreenNotifications::unread($user),
            'items' => $user->screenNotifications()
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get()
                ->map(fn (ScreenNotification $notification) => [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'link' => $notification->link,
                    'is_new' => $notification->seen_at === null,
                    'created_at' => $notification->created_at->toIso8601String(),
                ])
                ->all(),
        ];
    }
}
