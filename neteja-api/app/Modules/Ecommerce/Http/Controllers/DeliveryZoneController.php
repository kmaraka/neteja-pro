<?php

namespace App\Modules\Ecommerce\Http\Controllers;

use App\Modules\Ecommerce\Models\DeliveryZone;
use Illuminate\Http\JsonResponse;

class DeliveryZoneController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => DeliveryZone::active()->orderBy('commune')->get(['commune', 'fee_fcfa', 'estimated_delay']),
        ]);
    }
}
