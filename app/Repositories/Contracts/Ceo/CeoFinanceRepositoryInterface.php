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
}
