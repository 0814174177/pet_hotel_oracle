<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Web\WebController;
use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class PromotionController extends WebController
{
    public function index(): View
    {
        $coupons = Coupon::query()
            ->select([
                'coupon_id',
                'coupon_code',
                'employee_id',
                'discount_type',
                'discount_value',
                'max_discount',
                'min_order_value',
                'max_uses',
                'used_count',
                'effective_from',
                'expired_at',
                'is_active',
                'notes',
            ])
            ->orderBy('coupon_id')
            ->get()
            ->map(fn (Coupon $coupon): array => [
                'id' => (int) $coupon->coupon_id,
                'code' => $coupon->coupon_code,
                'empId' => $coupon->employee_id !== null ? (int) $coupon->employee_id : null,
                'type' => strtoupper((string) $coupon->discount_type),
                'value' => $this->numberOrNull($coupon->discount_value),
                'maxDiscount' => $this->numberOrNull($coupon->max_discount),
                'minOrder' => $this->numberOrNull($coupon->min_order_value),
                'maxUses' => $coupon->max_uses !== null ? (int) $coupon->max_uses : null,
                'usedCount' => (int) ($coupon->used_count ?? 0),
                'from' => $this->dateString($coupon->effective_from),
                'expired' => $this->dateString($coupon->expired_at),
                'active' => (bool) $coupon->is_active,
                'notes' => $coupon->notes,
            ])
            ->values();

        return view('pages.manager.promotion-management', [
            'coupons' => $coupons,
        ]);
    }

    private function numberOrNull(mixed $value): ?float
    {
        return $value !== null ? (float) $value : null;
    }

    private function dateString(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }
}
