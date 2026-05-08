<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(public string $token) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = rtrim((string) config('app.frontend_url'), '/')
            ."/reset-password?token={$this->token}&email={$notifiable->email}";

        return (new MailMessage)
            ->subject('Restablecer contrasena - Arcane Systems')
            ->greeting('Hola '.$notifiable->nombre.',')
            ->line('Recibimos una solicitud para restablecer tu contrasena.')
            ->action('Restablecer contrasena', $url)
            ->line('Este enlace expira en 60 minutos.')
            ->line('Si no solicitaste esto, ignora este correo.');
    }
}
