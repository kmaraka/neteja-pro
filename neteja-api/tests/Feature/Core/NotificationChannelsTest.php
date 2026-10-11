<?php

use App\Models\User;
use App\Modules\Core\Notifications\WelcomeNotification;
use App\Modules\Core\Sms\SmsChannel;
use Illuminate\Support\Facades\Notification;

it('envoie uniquement un SMS à un intervenant sans email', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => null, 'phone' => '+2250700000000']);

    $user->notify(new WelcomeNotification);

    Notification::assertSentTo($user, WelcomeNotification::class,
        fn ($notification, array $channels) => in_array(SmsChannel::class, $channels)
            && ! in_array('mail', $channels));
});
