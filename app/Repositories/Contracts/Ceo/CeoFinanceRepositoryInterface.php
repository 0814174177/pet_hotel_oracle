<?php

namespace App\Repositories\Contracts\Ceo;

interface CeoFinanceRepositoryInterface
{
    public function getFinanceData(array $filters = []): array;

    public function getTotalChainRevenueCard(array $filters = []): array;

    public function getEstimatedTotalCostCard(array $filters = []): array;

    public function getEstimatedProfitCard(array $filters = []): array;

    public function getEstimatedMarginCard(array $filters = []): array;

    public function getFinanceTrendChart(array $filters = []): array;

    public function getFinanceMonthlyTrendChart(array $filters = []): array;

    public function getCostStructureChart(array $filters = []): array;

    public function getBranchEstimatedProfitTable(array $filters = []): array;

    public function getServiceEstimatedProfitTable(array $filters = []): array;

    public function getLowestMarginServicesTable(array $filters = []): array;

    public function getNegativeBranchProfitAlerts(array $filters = [], float $profitThreshold = 0.0): array;

    public function getLowServiceMarginAlerts(
        array $filters = [],
        float $lowMarginThreshold = 20.0,
        float $highMarginThreshold = 10.0
    ): array;

    public function getCostGrowthAlerts(
        array $filters = [],
        float $costGrowthThreshold = 20.0,
        float $highCostGrowthThreshold = 40.0
    ): array;
}
