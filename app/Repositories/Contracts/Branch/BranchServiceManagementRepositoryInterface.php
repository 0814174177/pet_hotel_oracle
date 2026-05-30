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
     * Get service revenue progress for the selected branch and period.
     */
    public function getRevenueProgress(int|string $branchId, array $period = []): array;

    /**
     * Get the local top service for the selected branch and period.
     */
    public function getLocalTopService(int|string $branchId, array $period = []): ?array;

    /**
     * Get the upsell rate for the selected branch and period.
     */
    public function getUpsellRate(int|string $branchId, array $period = []): array;

    /**
     * Get services configured for the selected branch.
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
