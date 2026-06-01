<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::parse('2026-05-25 08:00:00');

        DB::table('audit_log')->insert([
            ['audit_id' => 1, 'table_name' => 'booking', 'action_type' => 'INSERT', 'row_pk' => '1', 'detail_text' => 'Seed booking demo cho Milo và Bella tại Gò Vấp.', 'changed_by_user_id' => 2, 'changed_at' => $now],
            ['audit_id' => 2, 'table_name' => 'orders', 'action_type' => 'INSERT', 'row_pk' => '4', 'detail_text' => 'Seed order đã thanh toán cho booking của Max.', 'changed_by_user_id' => 5, 'changed_at' => $now->copy()->addMinutes(5)],
            ['audit_id' => 3, 'table_name' => 'branch_inventory', 'action_type' => 'UPDATE', 'row_pk' => '1', 'detail_text' => 'Seed tồn kho demo cho vật tư tắm thú cưng.', 'changed_by_user_id' => 10, 'changed_at' => $now->copy()->addMinutes(10)],
        ]);
    }
}