<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Policies\AuditLogPolicy;
use App\Modules\Auth\Policies\RolePolicy;
use App\Modules\Auth\Policies\UserPolicy;
use App\Modules\Core\Sms\HttpSmsGateway;
use App\Modules\Core\Sms\LogSmsGateway;
use App\Modules\Core\Sms\SmsGateway;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, fn () => config('services.sms.driver') === 'http'
            ? new HttpSmsGateway
            : new LogSmsGateway);
    }

    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('login')).'|'.$request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('verification', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));

        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers());

        // Le super-admin passe toutes les vérifications de permission.
        Gate::before(fn ($user, $ability) => $user->hasRole('super-admin') ? true : null);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        // Le lien de l'email "mot de passe oublié" pointe vers la page React.
        ResetPassword::createUrlUsing(fn ($user, string $token) => config('neteja.frontend_url')
            .'/reset-password?token='.$token.'&email='.urlencode($user->getEmailForPasswordReset()));
    }
}
