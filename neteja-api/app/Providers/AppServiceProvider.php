<?php

namespace App\Providers;

use App\Modules\Core\Sms\HttpSmsGateway;
use App\Modules\Core\Sms\LogSmsGateway;
use App\Modules\Core\Sms\SmsGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Quand on demande un SmsGateway, Laravel fournit celui du .env.
        $this->app->bind(SmsGateway::class, fn () => config('services.sms.driver') === 'http'
            ? new HttpSmsGateway
            : new LogSmsGateway);
    }

    public function boot(): void
    {
        // 5 tentatives/minute par email + IP : freine le brute-force.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)
            ->by($request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));

        // Mot de passe : 8 caractères min, majuscules + minuscules + chiffres.
        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers());
    }
}
