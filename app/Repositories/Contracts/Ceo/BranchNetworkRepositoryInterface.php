<?php

namespace App\Repositories\Contracts\Ceo;

interface BranchNetworkRepositoryInterface
{
    public function getActiveBranchCount(array $filters = []): array;

    public function getHighestRevenueBranch(array $filters = []): ?array;

    public function getLowestOccupancyBranch(array $filters = []): ?array;

    public function getBranchNetworkList(array $filters = []): array;

    public function resolvePeriodRange(
        ?string $periodType,
        ?string $date = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array;
}
