<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanupSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Oracle does not support MySQL's SET FOREIGN_KEY_CHECKS. Delete rows
         * in reverse dependency order so foreign keys remain enabled.
         */
        $tables = [
            'payments',
            'booking_coupon_log',
            'order_details',
            'orders',
            'booking_service_pet',
            'booking_room_pet',
            'booking_room',
            'booking',
            'branch_inventory',
            'coupon',
            'pet',
            'customer',
            'employee',
            'service_product_detail',
            'services',
            'product',
            'category_services',
            'category_product',
            'room',
            'type_room',
            'branch',
            'audit_log',
            'sessions',
            'password_reset_tokens',
            'users',
            'failed_jobs',
            'job_batches',
            'jobs',
            'cache_locks',
            'cache',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
