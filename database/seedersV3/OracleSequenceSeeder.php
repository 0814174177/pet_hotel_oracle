<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OracleSequenceSeeder extends Seeder
{
    /**
     * Fixed demo IDs do not advance Yajra's Oracle sequences. Recreate them
     * after seeding so normal Eloquent creates continue from max(id) + 1.
     */
    public function run(): void
    {
        if (DB::connection()->getDriverName() !== 'oracle') {
            return;
        }

        foreach ($this->autoIncrementColumns() as $table => $primaryKey) {
            $nextValue = ((int) DB::table($table)->max($primaryKey)) + 1;
            $sequence = $this->sequenceName($table, $primaryKey);

            DB::statement("
                declare
                    e exception;
                    pragma exception_init(e, -02289);
                begin
                    execute immediate 'drop sequence {$sequence}';
                exception
                    when e then null;
                end;
            ");

            DB::statement("create sequence {$sequence} minvalue 1 start with {$nextValue} increment by 1");
        }
    }

    private function autoIncrementColumns(): array
    {
        return [
            'users' => 'id',
            'audit_log' => 'audit_id',
            'branch' => 'branch_id',
            'type_room' => 'type_room_id',
            'room' => 'room_id',
            'category_product' => 'product_category_id',
            'category_services' => 'service_category_id',
            'product' => 'product_id',
            'services' => 'service_id',
            'service_product_detail' => 'service_product_detail_id',
            'employee' => 'employee_id',
            'customer' => 'customer_id',
            'pet' => 'pet_id',
            'coupon' => 'coupon_id',
            'branch_inventory' => 'branch_inventory_id',
            'booking' => 'booking_id',
            'booking_room' => 'booking_room_id',
            'booking_room_pet' => 'booking_room_pet_id',
            'booking_service_pet' => 'booking_service_pet_id',
            'orders' => 'order_id',
            'order_details' => 'order_detail_id',
            'booking_coupon_log' => 'booking_coupon_log_id',
            'payments' => 'payment_id',
        ];
    }

    private function sequenceName(string $table, string $primaryKey): string
    {
        return substr($table.'_'.$primaryKey.'_seq', 0, (int) config('database.connections.oracle.max_name_len', 30));
    }
}
