<?php

namespace App\Http\Controllers\Api\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Branch\BranchServiceManagementRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchServiceManagementController extends Controller
{
    public function __construct(
        protected BranchServiceManagementRepositoryInterface $branchServiceManagementRepository
    ) {
    }

    public function index(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view service management data for this branch.
        $filters = $this->validateFilters($request);

        return response()->json([
            'success' => true,
            'data' => $this->branchServiceManagementRepository->getOverview($branchId, $filters),
        ]);
    }

    public function kpi(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view KPI cards for this branch.
        $filters = $this->validatePeriod($request);

        return response()->json([
            'success' => true,
            'data' => $this->branchServiceManagementRepository->getKpiCards($branchId, $filters),
        ]);
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
        // TODO: Authorize that the current user can view service revenue progress for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchServiceManagementRepository->getRevenueProgress(
                $branchId,
                $request->getFiltersArray()
            ),
        ]);
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
        // TODO: Authorize that the current user can view service revenue drop alerts for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchServiceManagementRepository->getRevenueDropAlerts(
                $branchId,
                $request->getFiltersArray()
            ),
        ]);
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
        // TODO: Authorize that the current user can view upsell KPI for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchServiceManagementRepository->getUpsellRate(
                $branchId,
                $request->getFiltersArray()
            ),
        ]);
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
        // TODO: Authorize that the current user can view service list data for this branch.
        $filters = $this->validateFilters($request);
        $filters['search'] = $this->pathFilterValue($search) ?? ($filters['search'] ?? null);
        $filters['service_group'] = $this->pathFilterValue($serviceGroup) ?? ($filters['service_group'] ?? null);
        $filters['status'] = $this->pathFilterValue($status) ?? ($filters['status'] ?? null);

        return response()->json([
            'success' => true,
            'data' => $this->branchServiceManagementRepository->getServiceList($branchId, $filters),
        ]);
    }

    public function updateWebsiteVisibility(Request $request, int|string $branchId, int|string $serviceId): JsonResponse
    {
        // TODO: Authorize that the current user can update website visibility for this branch service.
        $request->validate([
            'is_visible' => ['required', 'boolean'],
        ]);

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $this->branchServiceManagementRepository->updateWebsiteVisibility(
                $branchId,
                $serviceId,
                $request->boolean('is_visible')
            ),
        ]);
    }

    public function updateEmergencyLock(Request $request, int|string $branchId, int|string $serviceId): JsonResponse
    {
        // TODO: Authorize that the current user can lock or unlock this branch service.
        $validated = $request->validate([
            'is_locked' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['is_locked'] = $request->boolean('is_locked');

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $this->branchServiceManagementRepository->updateEmergencyLock($branchId, $serviceId, $validated),
        ]);
    }

    public function updatePriceOverride(Request $request, int|string $branchId, int|string $serviceId): JsonResponse
    {
        // TODO: Authorize that the current user can update branch-specific service pricing.
        $validated = $request->validate([
            'override_price' => ['present', 'nullable', 'numeric', 'min:0'],
        ]);

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $this->branchServiceManagementRepository->updatePriceOverride($branchId, $serviceId, $validated),
        ]);
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
