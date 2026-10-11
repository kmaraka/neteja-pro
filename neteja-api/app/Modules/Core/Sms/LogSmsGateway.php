<?php

namespace App\Modules\Core\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): void
    {
        Log::info("SMS vers {$to} : {$message}");
    }
}
