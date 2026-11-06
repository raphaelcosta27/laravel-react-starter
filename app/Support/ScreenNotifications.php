<?php

namespace App\Support;

use App\Models\ScreenNotification;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ScreenNotifications
{
    /**
     * Envia uma notificação a um usuário ou a vários, em uma chamada:
     *
     *     ScreenNotifications::send($profile->users, 'Título', 'Texto', 'success', '/dashboard');
     *
     * @param  User|iterable<User>  $to
     * @param  string  $type  info, success, warning ou error
     * @param  string|null  $link  caminho interno ou endereço http(s)
     *
     * @throws InvalidArgumentException
     */
    public static function send(User|iterable $to, string $title, string $body, string $type = 'info', ?string $link = null): void
    {
        if (! in_array($type, ScreenNotification::TYPES, true)) {
            throw new InvalidArgumentException("Tipo de notificação desconhecido: {$type}.");
        }

        if ($link !== null && preg_match('~^(/(?![/\\\\])|https?://)~i', $link) !== 1) {
            throw new InvalidArgumentException('O link deve ser um caminho interno ou um endereço http(s).');
        }

        Collection::wrap($to instanceof User ? [$to] : $to)->each(
            fn (User $user) => $user->screenNotifications()->create([
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'link' => $link,
            ]),
        );
    }

    public static function unread(User $user): int
    {
        return $user->screenNotifications()->whereNull('seen_at')->count();
    }
}
