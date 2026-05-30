<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Application\Dashboard\DashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $month = $request->query('month');
        $monthValue = is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1
            ? $month
            : now()->format('Y-m');

        return response()->json($this->dashboardService->overview($monthValue));
    }
}
