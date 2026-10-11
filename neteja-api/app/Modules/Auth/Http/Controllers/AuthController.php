<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Models\User;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Services\AuditLogger;
use App\Modules\Auth\Services\DeviceSessionService;
use App\Modules\Compliance\Services\ConsentService;
use App\Modules\Core\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController
{
    public function __construct(private AuditLogger $audit) {}

    public function register(RegisterRequest $request, ConsentService $consents): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $request, $consents) {
            // forceFill ignore la protection $fillable : sûr ici, car les données sont validées.
            $user = new User;
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'], // haché automatiquement (cast "hashed")
            ])->save();

            $user->assignRole('client');
            $consents->accept($user, array_keys(config('legal.versions')), $request->ip());

            return $user;
        });

        $user->sendEmailVerificationNotification();
        $this->audit->log('auth.register', 'auth', $user, user: $user);

        return response()->json([
            'message' => 'Compte créé. Vérifiez votre email pour l\'activer.',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $login = trim($request->input('login'));
        $field = str_contains($login, '@') ? 'email' : 'phone';
        $value = $field === 'email' ? Str::lower($login) : Phone::normalize($login);

        $guard = Auth::guard('web');

        if (! $guard->attempt([$field => $value, 'password' => $request->input('password')], $request->boolean('remember'))) {
            // Message volontairement vague : on ne révèle pas si le compte existe.
            throw ValidationException::withMessages(['login' => ['Identifiants incorrects.']]);
        }

        $user = $guard->user();

        // Un client ou apporteur doit avoir vérifié son email avant de se connecter.
        if ($user->email && ! $user->hasVerifiedEmail() && $user->hasAnyRole(['client', 'apporteur'])) {
            $guard->logout();

            return response()->json([
                'message' => 'Veuillez vérifier votre email avant de vous connecter.',
                'code' => 'email_not_verified',
            ], 403);
        }

        $request->session()->regenerate(); // protège contre la fixation de session
        $this->audit->log('auth.login', 'auth', $user, user: $user);

        return response()->json(['user' => new UserResource($user)]);
    }

    public function logout(Request $request): Response
    {
        $this->audit->log('auth.logout', 'auth', user: $request->user());

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /** Déconnecte tous les AUTRES appareils (utile en cas de perte de téléphone). */
    public function logoutAll(Request $request, DeviceSessionService $devices): Response
    {
        $devices->revokeOthers(
            $request->user(),
            $request->hasSession() ? $request->session()->getId() : null,
        );
        $this->audit->log('auth.logout_all', 'auth', user: $request->user());

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => new UserResource($request->user())]);
    }
}
