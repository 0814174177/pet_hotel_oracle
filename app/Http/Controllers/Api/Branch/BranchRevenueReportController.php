<?php

namespace App\Http\Controllers\Api\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Branch\BranchRevenueReportRepositoryInterface;
use Illuminate\Http\JsonResponse;

class BranchRevenueReportController extends Controller
{
    public function __construct(
        protected BranchRevenueReportRepositoryInterface $branchRevenueReportRepository
    ) {
    }

    public function index(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view this branch revenue report.
        return response()->json([
            'success' => true,
            'data' => $this->branchRevenueReportRepository->getDashboard($branchId, $this->filters($request)),
        ]);
    }

    public function targetProgress(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view revenue target progress for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchRevenueReportRepository->getTargetProgress($branchId, $this->filters($request)),
        ]);
    }

    public function revenueComparison(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view revenue comparison data for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchRevenueReportRepository->getRevenueComparison($branchId, $this->filters($request)),
        ]);
    }

    public function serviceMix(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view service mix and AOV data for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchRevenueReportRepository->getServiceMixAndAov($branchId, $this->filters($request)),
        ]);
    }

    public function employeePerformance(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view employee performance data for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchRevenueReportRepository->getEmployeePerformance($branchId, $this->filters($request)),
        ]);
    }

    public function customerRetention(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view customer retention data for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchRevenueReportRepository->getCustomerRetention($branchId, $this->filters($request)),
        ]);
    }

    private function filters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'period' => ['nullable', 'string', 'in:day,month,year'],
            'date' => ['nullable', 'date'],
        ]));
    }
}
