<?php

namespace App\Repositories\Contracts;

use App\Models\Coupon;
use Illuminate\Support\Collection;

interface PromotionRepositoryInterface
{
    public function managementCoupons(): Collection;

    public function createCoupon(array $validated, int $employeeId, bool $isActive): Coupon;

    public function deactivateCoupon(Coupon $coupon): void;
}
