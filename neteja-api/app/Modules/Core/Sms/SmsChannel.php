<?php

namespace App\Modules\Core\Sms;

use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(private SmsGateway $sms) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable->phone || ! method_exists($notification, 'toSms')) {
            return;
        }

        $this->sms->send($notifiable->phone, $notification->toSms($notifiable));
    }
}
