<?php

namespace App\Repositories\Contracts\Ceo;

interface CeoVendorRepositoryInterface
{
    public function getVendors(
        array $filters = [],
        float $platinumThreshold = 95.0,
        float $goldThreshold = 90.0,
        float $silverThreshold = 75.0,
        float $outOfStockPenalty = 18.0,
        float $lowStockPenalty = 8.0
    ): array;

    public function getOnTimeDeliveryPerformance(
        array $filters = [],
        float $excellentOtdThreshold = 95.0,
        float $goodOtdThreshold = 90.0,
        float $warningOtdThreshold = 80.0,
        float $lowQualityThreshold = 3.5,
        float $mediumQualityThreshold = 4.0,
        float $severeDelayHours = 24.0,
        float $warningDelayHours = 12.0,
        float $watchDelayHours = 3.0
    ): array;

    public function getOnTimeDeliverySummary(
        array $filters = [],
        float $goodOtdThreshold = 90.0,
        float $warningOtdThreshold = 80.0
    ): array;

    public function getPayableCashflow(
        array $filters = [],
        float $lightGrowthThreshold = 10.0,
        float $warningGrowthThreshold = 20.0,
        float $criticalGrowthThreshold = 35.0
    ): array;

    public function getPriceVarianceAlerts(
        array $filters = [],
        float $watchThreshold = 10.0,
        float $warningThreshold = 20.0,
        float $criticalThreshold = 30.0
    ): array;

    public function resolvePeriodRange(
        ?string $periodType,
        ?string $date = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array;
}
