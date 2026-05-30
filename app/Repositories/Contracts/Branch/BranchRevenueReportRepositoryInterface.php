<?php

namespace App\Repositories\Contracts\Branch;

interface BranchRevenueReportRepositoryInterface
{
    /**
     * Get the full branch revenue report dashboard structure.
     */
    public function getDashboard(int|string $branchId, array $filters = []): array;

    /**
     * Get revenue target progress for the selected branch and period.
     */
    public function getTargetProgress(
        int|string $branchId,
        string $period,
        ?string $startDate = null,
        ?string $endDate = null
    ): array;

    /**
     * Get current-period and previous-period revenue comparison data.
     */
    public function getRevenueComparison(
        int|string $branchId,
        string $period,
        ?string $startDate = null,
        ?string $endDate = null
    ): array;

    /**
     * Get service revenue mix and average order value data.
     */
    public function getServiceMixAndAov(
        int|string $branchId,
        string $period,
        ?string $startDate = null,
        ?string $endDate = null
    ): array;

    /**
     * Get service revenue mix grouped by service category.
     */
    public function getServiceRevenueMix(
        int|string $branchId,
        string $period,
        ?string $startDate = null,
        ?string $endDate = null
    ): array;

    /**
     * Get average order value data for the selected branch and period.
     */
    public function getAverageOrderValue(
        int|string $branchId,
        string $period,
        ?string $startDate = null,
        ?string $endDate = null
    ): array;

    /**
     * Get employee performance by revenue and upsell activity.
     */
    public function getEmployeePerformance(
        int|string $branchId,
        string $period,
        ?string $startDate = null,
        ?string $endDate = null
    ): array;

    /**
     * Get customer retention, new customer, and loyal customer metrics.
     */
    public function getCustomerRetention(
        int|string $branchId,
        string $period,
        ?string $startDate = null,
        ?string $endDate = null
    ): array;

    /**
     * Resolve the selected report period into a date range.
     */
    public function resolvePeriodRange(string $period, ?string $startDate = null, ?string $endDate = null): array;

    /**
     * Resolve the previous comparable report period into a date range.
     */
    public function resolvePreviousPeriodRange(string $period, ?string $startDate = null, ?string $endDate = null): array;
}
