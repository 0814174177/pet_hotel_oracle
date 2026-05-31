<?php

namespace App\Repositories\Eloquent\Ceo;

use App\Models\Product;
use App\Repositories\Contracts\Ceo\CeoVendorRepositoryInterface;

class CeoVendorRepository implements CeoVendorRepositoryInterface
{
    public function getVendors(array $filters = []): array
    {
        return [
            'message' => 'Danh sach doi tac/nha cung cap se duoc gan voi module supplier sau.',
            'products' => Product::orderBy('product_name')->limit(20)->get(),
        ];
    }

    public function resolvePeriodRange(
        ?string $periodType,
        ?string $date = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        // TODO: Resolve day/month/year or explicit date range before applying period-aware vendor spending metrics.
        return [];
    }
}
