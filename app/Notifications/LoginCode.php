<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Enviada de forma síncrona, para o código não ficar gravado em fila e para a
 * falha no envio chegar a quem está na tela.
 */
class LoginCode extends Notification
{
    public function __construct(public string $code) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your access code to :app', ['app' => config('app.name')]))
            ->line(__('Use this code to finish logging in: :code', ['code' => $this->code]))
            ->line(__('The code is valid for :minutes minutes.', ['minutes' => config('template.login.code_expire')]))
            ->line(__('If you did not try to log in, change your password.'));
    }
}
