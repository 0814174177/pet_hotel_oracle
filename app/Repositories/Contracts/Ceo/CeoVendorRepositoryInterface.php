<?php

namespace App\Repositories\Contracts\Ceo;

interface CeoVendorRepositoryInterface
{
    public function getVendors(array $filters = []): array;

    public function resolvePeriodRange(
        ?string $periodType,
        ?string $date = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array;
}
