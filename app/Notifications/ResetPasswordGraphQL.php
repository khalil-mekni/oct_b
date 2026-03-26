<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordGraphQL extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

        $resetUrl = $frontendUrl . '/reset-password/confirm?token=' . urlencode($this->token) . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Réinitialisation du mot de passe')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Vous recevez cet email car une demande de réinitialisation du mot de passe a été effectuée pour votre compte.')
            ->action('Réinitialiser le mot de passe', $resetUrl)
            ->line('Si vous n’avez pas demandé cette réinitialisation, aucune action n’est nécessaire.');
    }
}