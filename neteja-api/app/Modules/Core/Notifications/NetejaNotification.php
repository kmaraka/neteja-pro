<?php

namespace App\Modules\Core\Notifications;

use App\Modules\Core\Sms\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class NetejaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->email && $notifiable->hasVerifiedEmail()) {
            $channels[] = 'mail';
        }

        // SMS si pas d'email, ou si l'utilisateur préfère le SMS.
        if ($notifiable->phone && (! $notifiable->email || $notifiable->prefers_sms)) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
