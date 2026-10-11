<?php

namespace App\Modules\Compliance\Http\Controllers;

use App\Modules\Compliance\Services\ConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsentController
{
    public function pending(Request $request, ConsentService $consents): JsonResponse
    {
        return response()->json(['pending' => $consents->pending($request->user())]);
    }

    public function store(Request $request, ConsentService $consents): JsonResponse
    {
        $data = $request->validate([
            'types' => ['required', 'array', 'min:1'],
            'types.*' => ['string', Rule::in(array_keys(config('legal.versions')))],
        ]);

        $consents->accept($request->user(), $data['types'], $request->ip());

        return response()->json(['pending' => $consents->pending($request->user())]);
    }
}
