<?php

namespace App\Repositories\Contracts\Manager;

interface BranchScopedRevenueReportRepositoryInterface
{
    /**
     * Get the full branch revenue report dashboard structure.
     */
    public function getDashboard(int|string $branchId, array $filters = []): array;

    /**
     * Mo ta chuc nang:
     * Lay tien do muc tieu doanh thu thang cua chi nhanh Manager.
     */
    public function getTargetProgress(int|string $branchId, array $filters = []): array;

    /**
     * Mo ta chuc nang:
     * Lay bieu do so sanh doanh thu theo ngay trong ky.
     */
    public function getRevenueComparisonChart(int|string $branchId, array $filters = []): array;

    /**
     * Get current-period and previous-period revenue comparison data.
     */
    public function getRevenueComparison(int|string $branchId, array $filters = []): array;

    /**
     * Mo ta chuc nang:
     * Lay co cau doanh thu theo nhom dich vu cua chi nhanh Manager.
     */
    public function getServiceMix(int|string $branchId, array $filters = []): array;

    /**
     * Get service revenue mix and average order value data.
     */
    public function getServiceMixAndAov(int|string $branchId, array $filters = []): array;

    /**
     * Mo ta chuc nang:
     * Lay AOV, doanh thu va so don cua chi nhanh Manager.
     */
    public function getAovSummary(int|string $branchId, array $filters = []): array;

    /**
     * Get service revenue mix grouped by service category.
     */
    public function getServiceRevenueMix(int|string $branchId, array $filters = []): array;

    /**
     * Get average order value data for the selected branch and period.
     */
    public function getAverageOrderValue(int|string $branchId, array $filters = []): array;

    /**
     * Get employee performance by revenue and upsell activity.
     */
    public function getEmployeePerformance(int|string $branchId, array $filters = []): array;

    /**
     * Get customer retention, new customer, and loyal customer metrics.
     */
    public function getCustomerRetention(int|string $branchId, array $filters = []): array;

    /**
     * Resolve the selected report period into a date range.
     */
    public function resolvePeriodRange(array $filters = []): array;

    /**
     * Resolve the previous comparable report period into a date range.
     */
    public function resolvePreviousPeriodRange(array $filters = []): array;
}
