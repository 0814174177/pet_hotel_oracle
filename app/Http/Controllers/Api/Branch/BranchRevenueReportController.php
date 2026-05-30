<?php

namespace App\Http\Controllers\Api\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\RevenueReportRequest;
use App\Repositories\Contracts\Branch\BranchRevenueReportRepositoryInterface;
use Illuminate\Http\JsonResponse;

class BranchRevenueReportController extends Controller
{
    public function __construct(
        protected BranchRevenueReportRepositoryInterface $branchRevenueReportRepository
    ) {
    }

    public function index(RevenueReportRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view this branch revenue report.
        return response()->json([
            'data' => $this->branchRevenueReportRepository->getDashboard($branchId, $request->validated()),
        ]);
    }

    public function targetProgress(RevenueReportRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view revenue target progress for this branch.
        [$period, $startDate, $endDate] = $this->reportFilters($request);

        return response()->json([
            'data' => $this->branchRevenueReportRepository->getTargetProgress($branchId, $period, $startDate, $endDate),
        ]);
    }

    public function revenueComparison(RevenueReportRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view revenue comparison data for this branch.
        [$period, $startDate, $endDate] = $this->reportFilters($request);

        return response()->json([
            'data' => $this->branchRevenueReportRepository->getRevenueComparison($branchId, $period, $startDate, $endDate),
        ]);
    }

    public function serviceMix(RevenueReportRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view service mix and AOV data for this branch.
        [$period, $startDate, $endDate] = $this->reportFilters($request);

        return response()->json([
            'data' => $this->branchRevenueReportRepository->getServiceMixAndAov($branchId, $period, $startDate, $endDate),
        ]);
    }

    public function employeePerformance(RevenueReportRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view employee performance data for this branch.
        [$period, $startDate, $endDate] = $this->reportFilters($request);

        return response()->json([
            'data' => $this->branchRevenueReportRepository->getEmployeePerformance($branchId, $period, $startDate, $endDate),
        ]);
    }

    public function customerRetention(RevenueReportRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view customer retention data for this branch.
        [$period, $startDate, $endDate] = $this->reportFilters($request);

        return response()->json([
            'data' => $this->branchRevenueReportRepository->getCustomerRetention($branchId, $period, $startDate, $endDate),
        ]);
    }

    private function reportFilters(RevenueReportRequest $request): array
    {
        $validated = $request->validated();

        return [
            (string) ($validated['period'] ?? 'month'),
            $validated['start_date'] ?? $validated['date'] ?? null,
            $validated['end_date'] ?? null,
        ];
    }
}
