<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Enviada de forma síncrona, para a senha temporária não ficar gravada em fila.
 */
class WelcomeUser extends VerifyEmail
{
    public function __construct(public ?string $temporaryPassword = null) {}

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('Your access to :app', ['app' => config('app.name')]))
            ->line(__('Your account is ready. Use the details below to access it.'))
            ->line(__('Login: :email', ['email' => $notifiable->email]));

        if ($this->temporaryPassword !== null) {
            $message
                ->line(__('Temporary password: :password', ['password' => $this->temporaryPassword]))
                ->line(__('This password and the link below are valid for :minutes minutes.', [
                    'minutes' => config('template.temporary_password_expire'),
                ]));
        }

        return $message
            ->action(__('Confirm my account'), $this->verificationUrl($notifiable))
            ->line(__('After confirming your account, you will be asked to set a new password.'));
    }
}
