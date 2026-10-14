<?php

namespace App\Modules\Compliance\Http\Controllers;

use App\Modules\Auth\Services\AuditLogger;
use App\Modules\Compliance\Services\AccountErasureService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountController
{
    public function destroy(Request $request, AccountErasureService $erasure, AuditLogger $audit): Response
    {
        $user = $request->user();

        // Un seul bloc : si l'anonymisation échoue, la trace n'est pas écrite non plus.
        DB::transaction(function () use ($user, $erasure, $audit) {
            // Aucune donnée personnelle dans le journal : l'action et l'identifiant du compte suffisent.
            $audit->log('account.erased_by_user', 'compliance', $user, user: $user);

            $erasure->anonymize($user);
        });

        // Le compte n'existe plus : on ferme aussi la session du navigateur.
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }
}
