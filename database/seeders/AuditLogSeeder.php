<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('audit_log')) {
            return;
        }

        // Nếu database đã có trigger audit thật và trigger đã sinh log trong các seeder trước,
        // không insert ID cố định nữa để tránh trùng khóa chính.
        if (DB::table('audit_log')->exists()) {
            return;
        }

        $now = Carbon::parse('2026-05-25 08:00:00');

        DB::table('audit_log')->insert([
            ['audit_id' => 1, 'table_name' => 'booking', 'action_type' => 'INSERT', 'row_pk' => '1001', 'detail_text' => 'Seed booking demo đầu kỳ cho dữ liệu vận hành tháng 12/2025.', 'changed_by_user_id' => 2, 'changed_at' => $now],
            ['audit_id' => 2, 'table_name' => 'orders', 'action_type' => 'INSERT', 'row_pk' => '1001', 'detail_text' => 'Seed order thanh toán đầu tiên, đồng bộ với booking_id 1001.', 'changed_by_user_id' => 11, 'changed_at' => $now->copy()->addMinutes(5)],
            ['audit_id' => 3, 'table_name' => 'branch_inventory', 'action_type' => 'UPDATE', 'row_pk' => '1', 'detail_text' => 'Seed tồn kho demo cho vật tư tắm thú cưng.', 'changed_by_user_id' => 10, 'changed_at' => $now->copy()->addMinutes(10)],
            ['audit_id' => 4, 'table_name' => 'employee', 'action_type' => 'UPDATE', 'row_pk' => '18', 'detail_text' => 'Demo trạng thái nhân viên không hoạt động, không tham gia phân công booking/order.', 'changed_by_user_id' => 1, 'changed_at' => $now->copy()->addMinutes(15)],
        ]);
    }
}
