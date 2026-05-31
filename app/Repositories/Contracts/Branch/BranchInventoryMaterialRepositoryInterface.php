<?php

namespace App\Repositories\Contracts\Branch;

interface BranchInventoryMaterialRepositoryInterface
{
    /**
     * Get the full inventory material dashboard for the selected branch.
     */
    public function getDashboard(int|string $branchId, array $filters = []): array;

    /**
     * Get inventory material KPI cards for the selected branch and period.
     */
    public function getKpiCards(int|string $branchId, array $filters = []): array;

    /**
     * Get total material type count for the selected branch and period.
     */
    public function getTotalMaterialCount(int|string $branchId, array $filters = []): array;

    /**
     * Get out-of-stock material count for the selected branch and period.
     */
    public function getOutOfStockCount(int|string $branchId, array $filters = []): array;

    /**
     * Get low-stock material count for the selected branch and period.
     */
    public function getLowStockCount(int|string $branchId, array $filters = []): array;

    /**
     * Get inventory capital value for the selected branch and period.
     */
    public function getInventoryCapitalValue(int|string $branchId, array $filters = []): array;

    /**
     * Get inventory material list for the selected branch.
     */
    public function getMaterialList(int|string $branchId, array $filters = []): array;

    /**
     * Find inventory material detail for the selected branch.
     */
    public function findMaterialDetail(int|string $branchId, int|string $materialId): ?array;

    /**
     * Create a new inventory material for the selected branch.
     */
    public function createMaterial(int|string $branchId, array $data): array;

    /**
     * Update an inventory material for the selected branch.
     */
    public function updateMaterial(int|string $branchId, int|string $materialId, array $data): array;

    /**
     * Stop importing an inventory material for the selected branch.
     */
    public function stopImport(
        int|string $branchId,
        int|string $materialId,
        ?string $reason = null,
        int|string|null $userId = null
    ): array;

    /**
     * Resume importing an inventory material for the selected branch.
     */
    public function resumeImport(int|string $branchId, int|string $materialId, int|string|null $userId = null): array;

    /**
     * Resolve a report period into a date range.
     */
    public function resolvePeriodRange(array $filters = []): array;

    /**
     * Resolve material status from stock, warning threshold, and import status.
     */
    public function resolveMaterialStatus(
        float|int|null $currentStock,
        float|int|null $threshold,
        ?string $importStatus = null
    ): string;

    /**
     * Log an inventory material management action.
     */
    public function logMaterialAction(
        int|string $branchId,
        int|string $materialId,
        string $action,
        int|string|null $userId = null,
        array $metadata = []
    ): void;
}
