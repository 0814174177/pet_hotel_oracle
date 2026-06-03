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

        $rooms = [];
        $roomId = 101;

        $branchPrefixes = [
            1 => 'Q1',
            2 => 'TD',
            3 => 'Q7',
            4 => 'GV',
        ];

        foreach ($branchPrefixes as $branchId => $prefix) {
            /*
             * Mỗi chi nhánh có 30 phòng:
             * - 15 phòng chó: 8 nhỏ, 5 vừa, 2 lớn
             * - 15 phòng mèo: 8 nhỏ, 5 vừa, 2 lớn
             *
             * type_room_id:
             * 1 = Phòng nhỏ
             * 2 = Phòng vừa
             * 3 = Phòng lớn
             */

            // 8 phòng chó nhỏ: D101 - D108
            for ($i = 1; $i <= 8; $i++) {
                $rooms[] = [
                    'room_id' => $roomId++,
                    'branch_id' => $branchId,
                    'type_room_id' => 1,
                    'room_number' => $prefix . '-D10' . $i,
                    'status' => $i === 8 ? 'MAINTENANCE' : 'AVAILABLE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // 5 phòng chó vừa: D201 - D205
            for ($i = 1; $i <= 5; $i++) {
                $rooms[] = [
                    'room_id' => $roomId++,
                    'branch_id' => $branchId,
                    'type_room_id' => 2,
                    'room_number' => $prefix . '-D20' . $i,
                    'status' => 'AVAILABLE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // 2 phòng chó lớn: D301 - D302
            for ($i = 1; $i <= 2; $i++) {
                $rooms[] = [
                    'room_id' => $roomId++,
                    'branch_id' => $branchId,
                    'type_room_id' => 3,
                    'room_number' => $prefix . '-D30' . $i,
                    'status' => 'AVAILABLE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // 8 phòng mèo nhỏ: C101 - C108
            for ($i = 1; $i <= 8; $i++) {
                $rooms[] = [
                    'room_id' => $roomId++,
                    'branch_id' => $branchId,
                    'type_room_id' => 1,
                    'room_number' => $prefix . '-C10' . $i,
                    'status' => $i === 8 ? 'MAINTENANCE' : 'AVAILABLE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // 5 phòng mèo vừa: C201 - C205
            for ($i = 1; $i <= 5; $i++) {
                $rooms[] = [
                    'room_id' => $roomId++,
                    'branch_id' => $branchId,
                    'type_room_id' => 2,
                    'room_number' => $prefix . '-C20' . $i,
                    'status' => 'AVAILABLE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // 2 phòng mèo lớn: C301 - C302
            for ($i = 1; $i <= 2; $i++) {
                $rooms[] = [
                    'room_id' => $roomId++,
                    'branch_id' => $branchId,
                    'type_room_id' => 3,
                    'room_number' => $prefix . '-C30' . $i,
                    'status' => 'AVAILABLE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('room')->insert($rooms);
    }
}