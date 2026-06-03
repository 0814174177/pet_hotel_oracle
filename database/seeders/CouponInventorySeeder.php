<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CouponInventorySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::parse('2026-05-25 08:00:00');

        DB::table('coupon')->insert([
            ['coupon_id' => 1, 'coupon_code' => 'DEMO10', 'employee_id' => 2, 'discount_type' => 'PERCENT', 'discount_value' => 10, 'max_discount' => 100000, 'min_order_value' => 200000, 'max_uses' => 1000, 'used_count' => 0, 'effective_from' => '2025-12-01 00:00:00', 'expired_at' => '2026-12-31 23:59:59', 'is_active' => 1, 'notes' => 'Giảm 10% tối đa 100.000đ cho chiến dịch khai trương/demo.', 'created_at' => $now, 'updated_at' => $now],
            ['coupon_id' => 2, 'coupon_code' => 'WELCOME50', 'employee_id' => 3, 'discount_type' => 'FIXED', 'discount_value' => 50000, 'max_discount' => 50000, 'min_order_value' => 200000, 'max_uses' => 1000, 'used_count' => 0, 'effective_from' => '2025-12-01 00:00:00', 'expired_at' => '2026-12-31 23:59:59', 'is_active' => 1, 'notes' => 'Giảm cố định 50.000đ cho khách mới.', 'created_at' => $now, 'updated_at' => $now],
            ['coupon_id' => 3, 'coupon_code' => 'VIP15', 'employee_id' => 4, 'discount_type' => 'PERCENT', 'discount_value' => 15, 'max_discount' => 150000, 'min_order_value' => 500000, 'max_uses' => 1000, 'used_count' => 0, 'effective_from' => '2025-12-01 00:00:00', 'expired_at' => '2026-12-31 23:59:59', 'is_active' => 1, 'notes' => 'Mã VIP cho đơn lưu trú giá trị cao mùa lễ/Tết.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $rows = [];
        $id = 1;

        foreach ([1, 2, 3, 4] as $branchId) {
            foreach ([1, 2, 3, 4, 5, 6, 7] as $productId) {
                $rows[] = [
                    'branch_inventory_id' => $id++,
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'quantity_in_stock' => match ($productId) {
                        1 => 8000,
                        2 => 5000,
                        3 => 20000,
                        4 => 3500,
                        5 => 80,
                        6 => 500,
                        default => 30000,
                    },
                    'reorder_point' => match ($productId) {
                        5 => 20,
                        6 => 100,
                        default => 1000,
                    },
                    'last_updated' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('branch_inventory')->insert($rows);
    }
}
