<?php

namespace App\Repositories\Contracts\Ceo;

interface CeoDashboardRepositoryInterface
{
    public function getDashboard(array $filters = []): array;

    public function getDashboardData(array|string $filters = []): array;

    public function getOperationOverview(array $filters = []): array;

    public function getFinanceOverview(array $filters): array;

    public function getStrategyRiskOverview(array $filters = []): array;

    public function getCurrentHotelOccupancy(array $filters = []): array;

    public function getTotalRevenue(array $filters): array;

    public function getOccupancyRate(array $filters = []): array;

    public function getRevpar(array $filters = []): array;

    public function getCancellationRate(array $filters = []): array;

    public function getCustomerTrend(array $filters = []): array;

    public function getTotalChainRevenue(array $filters = []): array;

    public function getEstimatedCogs(array $filters = []): array;

    public function getTotalInventoryImportCost(array $filters = []): array;

    public function getRevenueMix(array $filters = []): array;

    public function getRevenueAndCogsTrend(array $filters = []): array;

    public function getRevenueTrend(array $filters = []): array;

    public function getBranchRevenue(array $filters = []): array;

    public function getBranchRanking(array $filters = []): array;

    public function getTopUsedServices(array $filters = []): array;

    public function getRiskAlerts(array $filters = []): array;

    public function getRiskAlertsLast30Days(array $filters = []): array;

    public function getDebugSummary(array $filters = []): array;

    public function resolvePeriodRange(
        ?string $period = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $date = null
    ): array;

    public function resolvePreviousPeriodRange(
        ?string $period = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $date = null
    ): array;

    public function formatPercentage(int|float|null $value): int|float|null;

    public function formatCurrency(int|float|null $value): int|float|null;
}