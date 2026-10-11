<?php

namespace App\Modules\Compliance\Http\Controllers;

use App\Modules\Compliance\Services\AccountErasureService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AccountController
{
    public function destroy(Request $request, AccountErasureService $erasure): Response
    {
        $erasure->anonymize($request->user());

        // Le compte n'existe plus : on ferme aussi la session du navigateur.
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }
}
