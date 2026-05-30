<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Repositories\Contracts\Manager\ManagerDashboardRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function __construct(
        protected ManagerDashboardRepositoryInterface $dashboardRepository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->overview($request);
    }

    public function overview(Request $request): JsonResponse
    {
        // TODO: Validate Manager dashboard filters before real business logic is implemented.
        $filters = $this->validateWithTimeFilters($request, [
            'branch_id' => ['nullable'],
        ]);

        return response()->json([
            'data' => $this->dashboardRepository->getOverview($filters),
        ]);
    }
}
