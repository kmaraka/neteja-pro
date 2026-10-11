<?php

namespace App\Modules\Compliance\Http\Middleware;

use App\Modules\Compliance\Services\ConsentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLatestConsents
{
    public function __construct(private ConsentService $consents) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $pending = $this->consents->pending($user);

            if ($pending !== []) {
                return response()->json([
                    'message' => 'Vous devez accepter les conditions en vigueur.',
                    'code' => 'consent_required',
                    'pending' => $pending,
                ], 403);
            }
        }

        return $next($request);
    }
}
