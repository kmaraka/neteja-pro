<?php

namespace App\Modules\Compliance\Http\Controllers;

use App\Modules\Compliance\Services\AccountErasureService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AccountController
{
    public function destroy(Request $request, AccountErasureService $erasure): Response
    {
        $erasure->anonymize($request->user());

        return response()->noContent();
    }
}
