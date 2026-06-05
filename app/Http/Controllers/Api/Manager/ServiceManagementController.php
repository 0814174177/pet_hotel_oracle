<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Manager\BranchScopedServiceManagementRepositoryInterface;
use App\Services\Manager\ManagerBranchScopeService;
use Illuminate\Http\JsonResponse;

class ServiceManagementController extends ApiController
{
    public function __construct(
        protected BranchScopedServiceManagementRepositoryInterface $branchServiceManagementRepository,
        protected ManagerBranchScopeService $branchScope
    ) {
    }

    public function index(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);
        $filters = $this->validateFilters($request);

        return $this->respondData(
            $this->branchServiceManagementRepository->getOverview($branchId, $filters)
        );
    }

    public function kpi(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);
        $filters = $this->validatePeriod($request);

        return $this->respondData(
            $this->branchServiceManagementRepository->getKpiCards($branchId, $filters)
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay KPI tien do doanh thu dich vu cua chi nhanh Manager trong ky loc.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh lay tu route.
     * - DateRangeFilterRequest: Tu xu ly start_date, end_date va ky truoc.
     *
     * Output:
     * - JSON { success: true, data: ... } gom doanh thu hien tai, chi tieu,
     *   phan tram hoan thanh, tang/giam va canh bao tien do.
     *
     * Ghi chu:
     * - Controller khong chua SQL va khong tinh KPI; chi truyen filters xuong Repository.
     */
    public function revenueProgress(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);
        $filters = array_merge($request->getFiltersArray(), $request->validate([
            'target_service_revenue' => ['nullable', 'numeric', 'min:0'],
        ]));

        return $this->respondData(
            $this->branchServiceManagementRepository->getRevenueProgress(
                $branchId,
                $filters
            )
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach canh bao dich vu co doanh thu giam manh tai chi nhanh Manager.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh lay tu route.
     * - DateRangeFilterRequest: Tu xu ly start_date, end_date va ky truoc.
     *
     * Output:
     * - JSON { success: true, data: ... } gom revenue_drop_alerts, summary va warning_level.
     *
     * Ghi chu:
     * - Controller khong chua SQL; chi truyen branchId va filters xuong Repository.
     */
    public function revenueDropAlerts(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchServiceManagementRepository->getRevenueDropAlerts(
                $branchId,
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay KPI ty le upsell cua booking phong tai chi nhanh Manager.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh lay tu route.
     * - DateRangeFilterRequest: Tu xu ly start_date, end_date va ky truoc.
     *
     * Output:
     * - JSON { success: true, data: ... } gom so booking phong, booking co dich vu,
     *   ty le upsell va muc danh gia.
     *
     * Ghi chu:
     * - Controller khong chua SQL; chi truyen branchId va filters xuong Repository.
     */
    public function upsellRate(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchServiceManagementRepository->getUpsellRate(
                $branchId,
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach dich vu quan tri cho chi nhanh Manager.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh lay tu route.
     * - DateRangeFilterRequest: Nhan filter ngay va bo loc danh sach neu co.
     *
     * Output:
     * - JSON { success: true, data: [...] } gom cac dong dich vu da normalize.
     *
     * Ghi chu:
     * - Controller khong chua SQL; chi truyen branchId va filters xuong Repository.
     */
    public function services(
        DateRangeFilterRequest $request,
        int|string $branchId,
        ?string $search = null,
        ?string $serviceGroup = null,
        ?string $status = null
    ): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);
        $filters = $this->validateFilters($request);
        $filters['search'] = $this->pathFilterValue($search) ?? ($filters['search'] ?? null);
        $filters['service_group'] = $this->pathFilterValue($serviceGroup) ?? ($filters['service_group'] ?? null);
        $filters['status'] = $this->pathFilterValue($status) ?? ($filters['status'] ?? null);

        return $this->respondData(
            $this->branchServiceManagementRepository->getServiceList($branchId, $filters)
        );
    }

    private function validateFilters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'period_type' => ['nullable', 'in:day,month,year'],
            'date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'service_group' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
        ]));
    }

    /**
     * Mo ta chuc nang:
     * Chuyen gia tri filter tren URL API ve filter Repository.
     *
     * Input:
     * - ?string $value: Gia tri path hoac "_" dai dien cho bo loc rong.
     *
     * Output:
     * - Chuoi filter da trim hoac null.
     *
     * Ghi chu:
     * - Dung de DashboardEngine giu nguyen cach goi GET voi query ngay hien co.
     */
    private function pathFilterValue(?string $value): ?string
    {
        if ($value === null || $value === '_') {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function validatePeriod(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'period_type' => ['nullable', 'in:day,month,year'],
            'date' => ['nullable', 'date'],
        ]));
    }
}
