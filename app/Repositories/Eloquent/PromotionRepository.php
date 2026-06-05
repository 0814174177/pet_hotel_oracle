<?php

namespace App\Repositories\Eloquent;

use App\Models\Coupon;
use App\Repositories\Contracts\PromotionRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PromotionRepository implements PromotionRepositoryInterface
{
    public function managementCoupons(): Collection
    {
        return Coupon::query()
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
            ->map(fn (Coupon $coupon): array => $this->formatManagementCoupon($coupon))
            ->values();
    }

    public function createCoupon(array $validated, int $employeeId, bool $isActive): Coupon
    {
        $discountValue = (float) $validated['discount_value'];
        $maxDiscount = $validated['discount_type'] === 'FIXED'
            ? $discountValue
            : $this->numberOrNull($validated['max_discount'] ?? null);

        return Coupon::create([
            'coupon_code' => $validated['coupon_code'],
            'employee_id' => $employeeId,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $discountValue,
            'max_discount' => $maxDiscount,
            'min_order_value' => $this->numberOrNull($validated['min_order_value'] ?? null) ?? 0,
            'max_uses' => isset($validated['max_uses']) ? (int) $validated['max_uses'] : null,
            'used_count' => 0,
            'effective_from' => Carbon::parse($validated['effective_from'])->startOfDay(),
            'expired_at' => Carbon::parse($validated['expired_at'])->endOfDay(),
            'is_active' => $isActive ? 1 : 0,
            'notes' => $validated['notes'] ?? null,
        ]);
    }

    public function deactivateCoupon(Coupon $coupon): void
    {
        $coupon->update([
            'is_active' => 0,
        ]);
    }

    private function formatManagementCoupon(Coupon $coupon): array
    {
        return [
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
        ];
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
