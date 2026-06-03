<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchRoomSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::parse('2026-05-25 08:00:00');

        DB::table('branch')->insert([
            ['branch_id' => 1, 'branch_name' => 'Pet Hotel Quận 1', 'phone' => '0901000001', 'email' => 'q1@pethotel.test', 'address' => '123 Nguyễn Huệ, Quận 1, TP.HCM', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['branch_id' => 2, 'branch_name' => 'Pet Hotel Thủ Đức', 'phone' => '0901000002', 'email' => 'thuduc@pethotel.test', 'address' => '456 Võ Văn Ngân, TP. Thủ Đức, TP.HCM', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['branch_id' => 3, 'branch_name' => 'Pet Hotel Quận 7', 'phone' => '0901000003', 'email' => 'q7@pethotel.test', 'address' => '789 Nguyễn Thị Thập, Quận 7, TP.HCM', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['branch_id' => 4, 'branch_name' => 'Pet Hotel Gò Vấp', 'phone' => '0901000004', 'email' => 'govap@pethotel.test', 'address' => '321 Phan Văn Trị, Gò Vấp, TP.HCM', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('type_room')->insert([
            ['type_room_id' => 1, 'type_name' => 'Phòng nhỏ', 'max_slot' => 2, 'pet_weight_min_kg' => 0.00, 'pet_weight_max_kg' => 10.00, 'base_price_per_day' => 150000, 'notes' => 'Phù hợp cho chó/mèo nhỏ dưới 10kg, tối đa 2 bé.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['type_room_id' => 2, 'type_name' => 'Phòng vừa', 'max_slot' => 2, 'pet_weight_min_kg' => 10.00, 'pet_weight_max_kg' => 25.00, 'base_price_per_day' => 220000, 'notes' => 'Phù hợp thú cưng từ 10kg đến 25kg.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['type_room_id' => 3, 'type_name' => 'Phòng lớn', 'max_slot' => 1, 'pet_weight_min_kg' => 25.00, 'pet_weight_max_kg' => 50.00, 'base_price_per_day' => 350000, 'notes' => 'Phòng rộng cho thú cưng lớn, mỗi phòng 1 bé.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('room')->insert([
            ['room_id' => 101, 'branch_id' => 1, 'type_room_id' => 1, 'room_number' => 'Q1-S101', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 102, 'branch_id' => 1, 'type_room_id' => 1, 'room_number' => 'Q1-S102', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 111, 'branch_id' => 1, 'type_room_id' => 2, 'room_number' => 'Q1-M201', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 112, 'branch_id' => 1, 'type_room_id' => 2, 'room_number' => 'Q1-M202', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 121, 'branch_id' => 1, 'type_room_id' => 3, 'room_number' => 'Q1-L301', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 122, 'branch_id' => 1, 'type_room_id' => 3, 'room_number' => 'Q1-L302', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],

            ['room_id' => 201, 'branch_id' => 2, 'type_room_id' => 1, 'room_number' => 'TD-S101', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 202, 'branch_id' => 2, 'type_room_id' => 1, 'room_number' => 'TD-S102', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 211, 'branch_id' => 2, 'type_room_id' => 2, 'room_number' => 'TD-M201', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 212, 'branch_id' => 2, 'type_room_id' => 2, 'room_number' => 'TD-M202', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 213, 'branch_id' => 2, 'type_room_id' => 2, 'room_number' => 'TD-M203', 'status' => 'MAINTENANCE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 221, 'branch_id' => 2, 'type_room_id' => 3, 'room_number' => 'TD-L301', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 222, 'branch_id' => 2, 'type_room_id' => 3, 'room_number' => 'TD-L302', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],

            ['room_id' => 301, 'branch_id' => 3, 'type_room_id' => 1, 'room_number' => 'Q7-S101', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 302, 'branch_id' => 3, 'type_room_id' => 1, 'room_number' => 'Q7-S102', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 311, 'branch_id' => 3, 'type_room_id' => 2, 'room_number' => 'Q7-M201', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 312, 'branch_id' => 3, 'type_room_id' => 2, 'room_number' => 'Q7-M202', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 321, 'branch_id' => 3, 'type_room_id' => 3, 'room_number' => 'Q7-L301', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 322, 'branch_id' => 3, 'type_room_id' => 3, 'room_number' => 'Q7-L302', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 323, 'branch_id' => 3, 'type_room_id' => 3, 'room_number' => 'Q7-L303', 'status' => 'MAINTENANCE', 'created_at' => $now, 'updated_at' => $now],

            ['room_id' => 401, 'branch_id' => 4, 'type_room_id' => 1, 'room_number' => 'GV-S101', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 402, 'branch_id' => 4, 'type_room_id' => 1, 'room_number' => 'GV-S102', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 411, 'branch_id' => 4, 'type_room_id' => 2, 'room_number' => 'GV-M201', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 412, 'branch_id' => 4, 'type_room_id' => 2, 'room_number' => 'GV-M202', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 421, 'branch_id' => 4, 'type_room_id' => 3, 'room_number' => 'GV-L301', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
            ['room_id' => 422, 'branch_id' => 4, 'type_room_id' => 3, 'room_number' => 'GV-L302', 'status' => 'AVAILABLE', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}