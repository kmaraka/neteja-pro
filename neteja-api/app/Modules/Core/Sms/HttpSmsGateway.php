<?php

namespace App\Modules\Core\Sms;

use Illuminate\Support\Facades\Http;

class HttpSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): void
    {
        // Le format exact du corps dépend de votre fournisseur : on l'adaptera.
        Http::withToken(config('services.sms.token'))
            ->timeout(10)
            ->post(config('services.sms.url'), ['to' => $to, 'message' => $message])
            ->throw();
    }
}
