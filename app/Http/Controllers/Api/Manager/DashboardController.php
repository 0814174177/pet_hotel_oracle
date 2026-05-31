<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Manager\ManagerDashboardRepositoryInterface;
use Illuminate\Http\JsonResponse;

class DashboardController extends ApiController
{
    public function __construct(
        protected ManagerDashboardRepositoryInterface $dashboardRepository
    ) {
    }

    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->overview($request);
    }

    public function overview(DateRangeFilterRequest $request): JsonResponse
    {
        $filters = array_merge($request->getFiltersArray(), $request->validate([
            'branch_id' => ['nullable'],
        ]));

        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getOverview($filters),
        ]);
    }
}
