<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Models\User;
use App\Modules\Auth\Services\AuditLogger;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmailVerificationController
{
    /** Cible du lien reçu par email (route web signée) : valide puis renvoie vers React. */
    public function verify(int $id, string $hash, AuditLogger $audit): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            $audit->log('auth.email_verified', 'auth', $user, user: $user);
        }

        return redirect(config('neteja.frontend_url').'/email-verifie');
    }

    /** Renvoi du lien. Réponse identique que le compte existe ou non (anti-énumération). */
    public function resend(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', Str::lower($request->input('email')))->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'Si le compte existe et n\'est pas vérifié, un nouvel email vient d\'être envoyé.',
        ], 202);
    }
}
