<?php

namespace App\Http\Controllers;

use App\Services\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function switch(Request $request, string $slug, CurrentTenant $currentTenant): JsonResponse
    {
        $tenant = $currentTenant->selectForUser($request->user(), $slug);

        return response()->json(['tenant' => $tenant]);
    }
}
