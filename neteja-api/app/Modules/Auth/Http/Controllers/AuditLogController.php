<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Models\AuditLog;
use Illuminate\Http\JsonResponse;

class AuditLogController
{
    public function index(): JsonResponse
    {
        return response()->json(
            AuditLog::query()->with('user:id,name')->latest('id')->paginate(25)
        );
    }
}
