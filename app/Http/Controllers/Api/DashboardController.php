<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\IndexDashboardRequest;
use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(
        IndexDashboardRequest $request,
        AdminDashboardService $dashboardService
    ): JsonResponse {
        $request->validated();

        return response()->json([
            'data' => $dashboardService->statistics(),
        ]);
    }
}
