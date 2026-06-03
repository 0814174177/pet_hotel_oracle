<?php

namespace App\Repositories\Contracts\Branch;

interface BranchServiceManagementRepositoryInterface
{
    /**
     * Get the full branch service management overview structure.
     */
    public function getOverview(int|string $branchId, array $filters = []): array;

    /**
     * Get KPI cards for branch service management.
     */
    public function getKpiCards(int|string $branchId, array $period = []): array;

    /**
     * Mo ta chuc nang:
     * Lay KPI tien do doanh thu dich vu cho chi nhanh va ky loc.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $period: start_date, end_date, prev_start_date, prev_end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang service_revenue gom doanh thu hien tai, ky truoc, chi tieu, tien do,
     *   tang/giam, canh bao va trend.
     *
     * Ghi chu:
     * - Implementation xu ly SQL Oracle va bind branch/date/target.
     */
    public function getRevenueProgress(int|string $branchId, array $period = []): array;

    /**
     * Mo ta chuc nang:
     * Lay danh sach dich vu co doanh thu giam manh so voi ky truoc tai chi nhanh.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $period: start_date, end_date, prev_start_date, prev_end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang gom revenue_drop_alerts, summary va warning_level.
     *
     * Ghi chu:
     * - Implementation chi tra canh bao khi doanh thu dich vu giam tu 20% tro len.
     */
    public function getRevenueDropAlerts(int|string $branchId, array $period = []): array;

    /**
     * Get the local top service for the selected branch and period.
     */
    public function getLocalTopService(int|string $branchId, array $period = []): ?array;

    /**
     * Mo ta chuc nang:
     * Lay KPI ty le upsell cua booking phong tai chi nhanh va ky loc.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $period: start_date, end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang upsell_rate gom tong booking phong, booking co them dich vu,
     *   ty le upsell, danh gia va warning_level.
     *
     * Ghi chu:
     * - Implementation xu ly SQL Oracle va bind branch/date.
     */
    public function getUpsellRate(int|string $branchId, array $period = []): array;

    /**
     * Mo ta chuc nang:
     * Lay danh sach dich vu quan tri cho chi nhanh Manager.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can hien thi danh sach.
     * - array $filters: Bo loc tim kiem, nhom dich vu va trang thai neu co.
     *
     * Output:
     * - Mang dich vu da normalize cho table/grid quan tri.
     *
     * Ghi chu:
     * - Schema hien tai chua co bang cau hinh dich vu theo chi nhanh.
     */
    public function getServiceList(int|string $branchId, array $filters = []): array;

    /**
     * Update website visibility for a service at the selected branch.
     */
    public function updateWebsiteVisibility(int|string $branchId, int|string $serviceId, bool $isVisible): array;

    /**
     * Update emergency lock status for a service at the selected branch.
     */
    public function updateEmergencyLock(int|string $branchId, int|string $serviceId, array $lockData): array;

    /**
     * Update branch-specific price override for a service.
     */
    public function updatePriceOverride(int|string $branchId, int|string $serviceId, array $priceData): array;

    /**
     * Resolve the reporting period into a date range.
     */
    public function resolvePeriodRange(?string $periodType, ?string $date = null): array;

    /**
     * Log an action made to a branch service configuration.
     */
    public function logServiceAction(
        int|string $branchId,
        int|string $serviceId,
        string $action,
        int|string|null $userId = null,
        array $metadata = []
    ): void;
}
