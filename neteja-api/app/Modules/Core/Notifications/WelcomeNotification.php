<?php

namespace App\Modules\Core\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class WelcomeNotification extends NetejaNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue chez Neteja')
            ->line('Merci de nous faire confiance.');
    }

    public function toSms(object $notifiable): string
    {
        return 'Bienvenue chez Neteja — Docteur Maison !';
    }

    public function toArray(object $notifiable): array
    {
        return ['message' => 'Bienvenue chez Neteja'];
    }
}
