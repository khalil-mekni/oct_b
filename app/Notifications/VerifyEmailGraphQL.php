<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailGraphQL extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $token = base64_encode($notifiable->id);

        $url = "http://localhost:8000/graphql?verifyEmail=".$token;

        return (new MailMessage)
            ->subject('Verify Email')
            ->line('Click the button to verify your email.')
            ->action('Verify Email', $url)
            ->line('Thank you for using our application!');
    }
}