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
use App\Modules\Ecommerce\Models\Category;
use App\Modules\Ecommerce\Models\DeliveryZone;
use App\Modules\Ecommerce\Models\Order;
use App\Modules\Ecommerce\Models\Product;
use App\Modules\Ecommerce\Models\PromoCode;
use App\Modules\Ecommerce\Policies\CategoryPolicy;
use App\Modules\Ecommerce\Policies\DeliveryZonePolicy;
use App\Modules\Ecommerce\Policies\OrderPolicy;
use App\Modules\Ecommerce\Policies\ProductPolicy;
use App\Modules\Ecommerce\Policies\PromoCodePolicy;
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
        // Quand on demande un SmsGateway, Laravel fournit celui du .env (SMS_DRIVER=log ou http).
        $this->app->bind(SmsGateway::class, fn () => config('services.sms.driver') === 'http'
            ? new HttpSmsGateway
            : new LogSmsGateway);
    }

    public function boot(): void
    {
        // --- Limiteurs de débit ---
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('login')).'|'.$request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('verification', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));

        // Essais de codes promo : 10 par minute, pour freiner la devinette de codes.
        RateLimiter::for('promo', fn (Request $request) => Limit::perMinute(10)
            ->by($request->user('sanctum')?->id ?: $request->ip()));

        // --- Mots de passe : 8 caractères min, majuscules + minuscules + chiffres ---
        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers());

        // --- Autorisations ---
        // Le super-admin passe toutes les vérifications de permission.
        Gate::before(fn ($user, $ability) => $user->hasRole('super-admin') ? true : null);

        // Nos modèles sont dans app/Modules : les policies sont déclarées explicitement.
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(DeliveryZone::class, DeliveryZonePolicy::class);
        Gate::policy(PromoCode::class, PromoCodePolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);

        // Le lien de l'email « mot de passe oublié » pointe vers la page React.
        ResetPassword::createUrlUsing(fn ($user, string $token) => config('neteja.frontend_url')
            .'/reset-password?token='.$token.'&email='.urlencode($user->getEmailForPasswordReset()));
    }
}
