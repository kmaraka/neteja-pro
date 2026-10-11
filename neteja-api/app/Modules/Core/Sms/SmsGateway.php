<?php

namespace App\Modules\Core\Sms;

interface SmsGateway
{
    public function send(string $to, string $message): void;
}
