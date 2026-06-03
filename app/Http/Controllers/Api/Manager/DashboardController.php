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

    /**
     * Mo ta chuc nang:
     * Lay KPI tong quan lich chi nhanh cho Manager theo chi nhanh cua user hien tai.
     *
     * Input:
     * - DateRangeFilterRequest tu dong xu ly start_date, end_date va ky truoc.
     *
     * Output:
     * - JSON { success: true, data: ... } gom KPI lich chi nhanh.
     *
     * Ghi chu:
     * - Controller khong dat SQL va khong tinh toan KPI.
     */
    public function overview(DateRangeFilterRequest $request): JsonResponse
    {
        $data = $this->dashboardRepository->getOverview(
            $this->currentBranchId($request),
            $request->getFiltersArray()
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay KPI tong quan lich chi nhanh cho Manager theo branchId tu route.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh lay tu route /branches/{branchId}/overview.
     * - DateRangeFilterRequest tu dong xu ly start_date, end_date va ky truoc.
     *
     * Output:
     * - JSON { success: true, data: ... } gom KPI lich chi nhanh.
     *
     * Ghi chu:
     * - Controller chi truyen branchId va filters xuong Repository.
     */
    public function branchOverview(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getOverview(
                $branchId,
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay canh bao ton kho cho Manager theo chi nhanh cua user hien tai.
     *
     * Input:
     * - DateRangeFilterRequest giu luong filter dashboard chung.
     *
     * Output:
     * - JSON { success: true, data: { current: ... } } gom so vat tu het/sap het.
     *
     * Ghi chu:
     * - Controller khong dat SQL va khong tinh severity.
     */
    public function inventoryWarning(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getInventoryWarning(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay canh bao ton kho cho Manager theo branchId tu route.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh lay tu route.
     * - DateRangeFilterRequest giu luong filter dashboard chung.
     *
     * Output:
     * - JSON { success: true, data: { current: ... } } gom so vat tu het/sap het.
     *
     * Ghi chu:
     * - Controller chi truyen branchId va filters xuong Repository.
     */
    public function branchInventoryWarning(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getInventoryWarning(
                $branchId,
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay canh bao y te tam thoi cho Manager theo chi nhanh cua user hien tai.
     *
     * Input:
     * - DateRangeFilterRequest cung cap start_date va end_date cua dashboard.
     *
     * Output:
     * - JSON { success: true, data: { current: ... } } gom so pet can theo doi.
     *
     * Ghi chu:
     * - Controller khong dat SQL va khong tinh severity.
     */
    public function healthWarning(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getHealthWarning(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay canh bao y te tam thoi cho Manager theo branchId tu route.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh lay tu route.
     * - DateRangeFilterRequest cung cap start_date va end_date cua dashboard.
     *
     * Output:
     * - JSON { success: true, data: { current: ... } } gom so pet can theo doi.
     *
     * Ghi chu:
     * - Controller chi truyen branchId va filters xuong Repository.
     */
    public function branchHealthWarning(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getHealthWarning(
                $branchId,
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay canh bao rui ro tai chinh cho Manager theo chi nhanh cua user hien tai.
     *
     * Input:
     * - DateRangeFilterRequest cung cap start_date va end_date cua dashboard.
     *
     * Output:
     * - JSON { success: true, data: { current: ... } } gom so don huy/hoan va that thoat.
     *
     * Ghi chu:
     * - Controller khong dat SQL va khong tinh severity.
     */
    public function financialRiskWarning(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getFinancialRiskWarning(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay canh bao rui ro tai chinh cho Manager theo branchId tu route.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh lay tu route.
     * - DateRangeFilterRequest cung cap start_date va end_date cua dashboard.
     *
     * Output:
     * - JSON { success: true, data: { current: ... } } gom so don huy/hoan va that thoat.
     *
     * Ghi chu:
     * - Controller chi truyen branchId va filters xuong Repository.
     */
    public function branchFinancialRiskWarning(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getFinancialRiskWarning(
                $branchId,
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach booking huy sat gio cho Manager theo chi nhanh cua user hien tai.
     *
     * Input:
     * - DateRangeFilterRequest cung cap start_date va end_date cua dashboard.
     *
     * Output:
     * - JSON { success: true, data: { current: { total_late_cancelled_bookings, items } } }.
     *
     * Ghi chu:
     * - Controller khong dat SQL va khong tinh severity tung dong.
     */
    public function lateCancelledBookings(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getLateCancelledBookings(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach booking huy sat gio cho Manager theo branchId tu route.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh lay tu route.
     * - DateRangeFilterRequest cung cap start_date va end_date cua dashboard.
     *
     * Output:
     * - JSON { success: true, data: { current: { total_late_cancelled_bookings, items } } }.
     *
     * Ghi chu:
     * - Controller chi truyen branchId va filters xuong Repository.
     */
    public function branchLateCancelledBookings(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getLateCancelledBookings(
                $branchId,
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay top 5 dich vu co doanh thu cao nhat cho Manager theo chi nhanh cua user hien tai.
     *
     * Input:
     * - DateRangeFilterRequest cung cap start_date va end_date cua dashboard.
     *
     * Output:
     * - JSON { success: true, data: { current: { items } } }.
     *
     * Ghi chu:
     * - Controller khong dat SQL va khong tinh ranking.
     */
    public function topRevenueServices(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getTopRevenueServices(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay top 5 dich vu co doanh thu cao nhat cho Manager theo branchId tu route.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh lay tu route.
     * - DateRangeFilterRequest cung cap start_date va end_date cua dashboard.
     *
     * Output:
     * - JSON { success: true, data: { current: { items } } }.
     *
     * Ghi chu:
     * - Controller chi truyen branchId va filters xuong Repository.
     */
    public function branchTopRevenueServices(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getTopRevenueServices(
                $branchId,
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay co cau doanh thu Pet Hotel, Grooming & Spa va Khac theo chi nhanh user hien tai.
     *
     * Input:
     * - DateRangeFilterRequest cung cap start_date va end_date cua dashboard.
     *
     * Output:
     * - JSON { success: true, data: { current: { items } } }.
     *
     * Ghi chu:
     * - Controller khong dat SQL va khong tinh ty trong doanh thu.
     */
    public function revenueStructure(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getRevenueStructure(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay co cau doanh thu Pet Hotel, Grooming & Spa va Khac theo branchId tu route.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh lay tu route.
     * - DateRangeFilterRequest cung cap start_date va end_date cua dashboard.
     *
     * Output:
     * - JSON { success: true, data: { current: { items } } }.
     *
     * Ghi chu:
     * - Controller chi truyen branchId va filters xuong Repository.
     */
    public function branchRevenueStructure(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getRevenueStructure(
                $branchId,
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach hoa don chua thanh toan du cho Manager theo chi nhanh user hien tai.
     *
     * Input:
     * - DateRangeFilterRequest cung cap start_date va end_date cua dashboard.
     *
     * Output:
     * - JSON { success: true, data: { current: { total_unpaid_invoices, total_remaining_debt, items } } }.
     *
     * Ghi chu:
     * - Controller khong dat SQL va khong tinh cong no.
     */
    public function unpaidInvoices(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getUnpaidInvoices(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach hoa don chua thanh toan du cho Manager theo branchId tu route.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh lay tu route.
     * - DateRangeFilterRequest cung cap start_date va end_date cua dashboard.
     *
     * Output:
     * - JSON { success: true, data: { current: { total_unpaid_invoices, total_remaining_debt, items } } }.
     *
     * Ghi chu:
     * - Controller chi truyen branchId va filters xuong Repository.
     */
    public function branchUnpaidInvoices(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardRepository->getUnpaidInvoices(
                $branchId,
                $request->getFiltersArray()
            ),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Xac dinh chi nhanh hien tai cua Manager cho route overview cu.
     *
     * Input:
     * - DateRangeFilterRequest co user dang dang nhap.
     *
     * Output:
     * - branch_id cua employee hien tai hoac 0 neu khong co de SQL khong lay toan he thong.
     *
     * Ghi chu:
     * - Khong dung branch_id query cho luong dashboard chinh.
     */
    private function currentBranchId(DateRangeFilterRequest $request): int|string
    {
        return $request->user()?->employee?->branch_id ?? 0;
    }
}
