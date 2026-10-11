<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Models\User;
use App\Modules\Auth\Services\AuditLogger;
use App\Modules\Auth\Services\DeviceSessionService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker; // alias : évite le conflit de nom
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordController
{
    public function __construct(private AuditLogger $audit, private DeviceSessionService $devices) {}

    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        PasswordBroker::sendResetLink(['email' => Str::lower($request->input('email'))]);

        // Même réponse dans tous les cas : on ne révèle pas si l'email existe.
        return response()->json([
            'message' => 'Si un compte existe avec cette adresse, un lien vient d\'être envoyé.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $status = PasswordBroker::reset(
            [
                'email' => Str::lower($request->input('email')),
                'password' => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $request->input('token'),
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'must_change_password' => false,
                    'remember_token' => Str::random(60),
                ])->save();

                $this->devices->revokeOthers($user); // tous les appareils sont déconnectés
                $this->audit->log('auth.password_reset', 'auth', $user, user: $user);
                event(new PasswordReset($user));
            },
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => ['Lien invalide ou expiré.']]);
        }

        return response()->json(['message' => 'Mot de passe réinitialisé. Vous pouvez vous connecter.']);
    }

    /** Changement volontaire, ou obligatoire après un mot de passe temporaire. */
    public function change(Request $request): Response
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['Mot de passe actuel incorrect.']]);
        }

        $user->forceFill([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ])->save();

        $this->devices->revokeOthers(
            $user,
            $request->hasSession() ? $request->session()->getId() : null,
        );
        $this->audit->log('auth.password_changed', 'auth', $user, user: $user);

        return response()->noContent();
    }
}
