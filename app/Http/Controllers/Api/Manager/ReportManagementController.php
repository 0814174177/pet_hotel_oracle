<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Manager\BranchScopedRevenueReportRepositoryInterface;
use App\Services\Manager\ManagerBranchScopeService;
use Illuminate\Http\JsonResponse;

class ReportManagementController extends ApiController
{
    public function __construct(
        protected BranchScopedRevenueReportRepositoryInterface $branchRevenueReportRepository,
        protected ManagerBranchScopeService $branchScope
    ) {
    }

    public function index(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getDashboard($branchId, $this->filters($request))
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay tien do muc tieu doanh thu thang cua chi nhanh Manager.
     *
     * Input:
     * - DateRangeFilterRequest $request: Bo loc start_date/end_date va ky truoc neu can.
     * - int|string $branchId: Ma chi nhanh lay tu route.
     *
     * Output:
     * - JSON { success: true, data: ... } gom revenue, target, progress va canh bao.
     *
     * Ghi chu:
     * - Controller khong viet SQL, chi truyen branchId va filters xuong Repository.
     */
    public function targetProgress(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getTargetProgress($branchId, $this->filters($request))
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay bieu do so sanh doanh thu theo ngay trong ky cua chi nhanh Manager.
     *
     * Input:
     * - DateRangeFilterRequest $request: Bo loc start_date, end_date va prev_start_date.
     * - int|string $branchId: Ma chi nhanh lay tu route.
     *
     * Output:
     * - JSON { success: true, data: [...] } gom doanh thu tung ngay ky nay va ky truoc.
     *
     * Ghi chu:
     * - Controller khong viet SQL, chi goi Repository.
     */
    public function revenueComparison(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getRevenueComparisonChart($branchId, $this->filters($request))
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay Service Mix va co cau doanh thu theo nhom dich vu cua chi nhanh Manager.
     *
     * Input:
     * - DateRangeFilterRequest $request: Bo loc start_date va end_date.
     * - int|string $branchId: Ma chi nhanh lay tu route.
     *
     * Output:
     * - JSON { success: true, data: [...] } gom revenue_group, revenue, percent_of_total.
     *
     * Ghi chu:
     * - Controller khong viet SQL, chi goi Repository.
     */
    public function serviceMix(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getServiceMix($branchId, $this->filters($request))
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay AOV, doanh thu va so don theo ky cua chi nhanh Manager.
     *
     * Input:
     * - DateRangeFilterRequest $request: Bo loc start_date, end_date va ky truoc.
     * - int|string $branchId: Ma chi nhanh lay tu route.
     *
     * Output:
     * - JSON { success: true, data: ... } gom current/previous AOV, order count va growth.
     *
     * Ghi chu:
     * - Controller khong viet SQL, chi goi Repository.
     */
    public function aovSummary(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getAovSummary($branchId, $this->filters($request))
        );
    }

    public function employeePerformance(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getEmployeePerformance($branchId, $this->filters($request))
        );
    }

    public function customerRetention(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getCustomerRetention($branchId, $this->filters($request))
        );
    }

    private function filters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'period' => ['nullable', 'string', 'in:day,month,year'],
            'date' => ['nullable', 'date'],
        ]));
    }
}
