<?php

namespace App\Repositories\Contracts\Manager;

interface BranchScopedInventoryMaterialRepositoryInterface
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
     * Get the normalized inventory KPI overview for the selected branch.
     */
    public function getInventoryKpi(int|string $branchId, array $filters = []): array;

    /**
     * Compare current material type count with the previous comparable period.
     */
    public function getMaterialTypeComparison(int|string $branchId, array $filters = []): array;

    /**
     * Get total material type count for the selected branch and period.
     */
    public function getTotalMaterialCount(int|string $branchId, array $filters = []): array;

    /**
     * Get out-of-stock material count for the selected branch and period.
     */
    public function getOutOfStockCount(int|string $branchId, array $filters = []): array;

    /**
     * Get out-of-stock materials that need immediate import for the selected branch.
     */
    public function getOutOfStockMaterials(int|string $branchId, array $filters = []): array;

    /**
     * Get low-stock materials for the selected branch.
     */
    public function getLowStockMaterials(int|string $branchId, array $filters = []): array;

    /**
     * Get low-stock material count for the selected branch and period.
     */
    public function getLowStockCount(int|string $branchId, array $filters = []): array;

    /**
     * Get inventory capital value for the selected branch and period.
     */
    public function getInventoryCapitalValue(int|string $branchId, array $filters = []): array;

    /**
     * Get inventory capital value grouped by material category for the selected branch.
     */
    public function getInventoryValueByCategory(int|string $branchId, array $filters = []): array;

    /**
     * Get inventory material list for the selected branch.
     */
    public function getMaterialList(int|string $branchId, array $filters = []): array;

    /**
     * Get inventory material list for the selected branch.
     */
    public function getMaterialsByBranch(int|string $branchId, array $filters = []): array;

    /**
     * Find inventory material detail for the selected branch.
     */
    public function findMaterialDetail(int|string $branchId, int|string $materialId): ?array;

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

}
