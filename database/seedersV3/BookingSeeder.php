<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::parse('2026-05-25 08:00:00');
        $day21 = Carbon::parse('2026-05-21 09:00:00');
        $day23 = Carbon::parse('2026-05-23 12:00:00');
        $day26Morning = Carbon::parse('2026-05-26 09:00:00');
        $day26Early = Carbon::parse('2026-05-26 08:00:00');
        $day26Late = Carbon::parse('2026-05-26 10:00:00');
        $day27Noon = Carbon::parse('2026-05-27 12:00:00');
        $day28Noon = Carbon::parse('2026-05-28 12:00:00');

        DB::table('booking')->insert([
            ['booking_id' => 1, 'customer_id' => 1, 'branch_id' => 4, 'checkin_expected_at' => $day26Morning, 'checkout_expected_at' => $day27Noon, 'checkin_actual_at' => null, 'checkout_actual_at' => null, 'status' => 'CONFIRMED', 'total_amount' => 360000, 'special_notes' => 'Nguyễn Minh Ánh đặt phòng nhỏ cho Milo và Bella.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_id' => 2, 'customer_id' => 2, 'branch_id' => 4, 'checkin_expected_at' => $day26Late, 'checkout_expected_at' => $day28Noon, 'checkin_actual_at' => null, 'checkout_actual_at' => null, 'status' => 'CONFIRMED', 'total_amount' => 603000, 'special_notes' => 'Trần Gia Huy đặt phòng vừa cho Lucky.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_id' => 3, 'customer_id' => 4, 'branch_id' => 4, 'checkin_expected_at' => $day26Early, 'checkout_expected_at' => $day27Noon, 'checkin_actual_at' => null, 'checkout_actual_at' => null, 'status' => 'CONFIRMED', 'total_amount' => 370000, 'special_notes' => 'Booking phụ chiếm phòng vừa thứ hai tại Gò Vấp.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_id' => 4, 'customer_id' => 3, 'branch_id' => 2, 'checkin_expected_at' => $day21, 'checkout_expected_at' => $day23, 'checkin_actual_at' => Carbon::parse('2026-05-21 08:45:00'), 'checkout_actual_at' => Carbon::parse('2026-05-23 11:45:00'), 'status' => 'COMPLETED', 'total_amount' => 1150000, 'special_notes' => 'Booking đã hoàn tất cho Max để demo lịch sử và thanh toán.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_id' => 5, 'customer_id' => 5, 'branch_id' => 1, 'checkin_expected_at' => $day26Morning, 'checkout_expected_at' => $day27Noon, 'checkin_actual_at' => null, 'checkout_actual_at' => null, 'status' => 'CONFIRMED', 'total_amount' => 200000, 'special_notes' => 'Chi nhánh Quận 1 có booking phòng nhỏ.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_id' => 6, 'customer_id' => 6, 'branch_id' => 3, 'checkin_expected_at' => $day26Morning, 'checkout_expected_at' => $day27Noon, 'checkin_actual_at' => null, 'checkout_actual_at' => null, 'status' => 'CONFIRMED', 'total_amount' => 570000, 'special_notes' => 'Chi nhánh Quận 7 có booking phòng lớn gần hết phòng.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('booking_room')->insert([
            ['booking_room_id' => 1, 'booking_id' => 1, 'room_id' => 401, 'assigned_at' => $now, 'notes' => 'Gán phòng GV-S101 cho Milo và Bella.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_room_id' => 2, 'booking_id' => 2, 'room_id' => 411, 'assigned_at' => $now, 'notes' => 'Gán phòng GV-M201 cho Lucky.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_room_id' => 3, 'booking_id' => 3, 'room_id' => 412, 'assigned_at' => $now, 'notes' => 'Gán phòng GV-M202 cho Coco.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_room_id' => 4, 'booking_id' => 4, 'room_id' => 221, 'assigned_at' => $now, 'notes' => 'Gán phòng TD-L301 cho Max.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_room_id' => 5, 'booking_id' => 5, 'room_id' => 101, 'assigned_at' => $now, 'notes' => 'Gán phòng Q1-S101 cho Nori.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_room_id' => 6, 'booking_id' => 6, 'room_id' => 321, 'assigned_at' => $now, 'notes' => 'Gán phòng Q7-L301 cho Rocky.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('booking_room_pet')->insert([
            ['booking_room_pet_id' => 1, 'booking_room_id' => 1, 'pet_id' => 1, 'notes' => 'Milo ở phòng nhỏ.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_room_pet_id' => 2, 'booking_room_id' => 1, 'pet_id' => 2, 'notes' => 'Bella ở cùng Milo, không vượt slot.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_room_pet_id' => 3, 'booking_room_id' => 2, 'pet_id' => 3, 'notes' => 'Lucky ở phòng vừa.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_room_pet_id' => 4, 'booking_room_id' => 3, 'pet_id' => 5, 'notes' => 'Coco ở phòng vừa thứ hai.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_room_pet_id' => 5, 'booking_room_id' => 4, 'pet_id' => 4, 'notes' => 'Max ở phòng lớn.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_room_pet_id' => 6, 'booking_room_id' => 5, 'pet_id' => 6, 'notes' => 'Nori ở phòng nhỏ Quận 1.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_room_pet_id' => 7, 'booking_room_id' => 6, 'pet_id' => 7, 'notes' => 'Rocky ở phòng lớn Quận 7.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('booking_service_pet')->insert([
            ['booking_service_pet_id' => 1, 'booking_id' => 1, 'pet_id' => 1, 'service_id' => 1, 'employee_id' => 6, 'scheduled_at' => Carbon::parse('2026-05-26 13:00:00'), 'status' => 'DONE', 'notes' => 'Milo đã hoàn tất dịch vụ tắm thú cưng.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_service_pet_id' => 2, 'booking_id' => 1, 'pet_id' => 2, 'service_id' => 1, 'employee_id' => 6, 'scheduled_at' => Carbon::parse('2026-05-26 14:00:00'), 'status' => 'DONE', 'notes' => 'Bella đã hoàn tất dịch vụ tắm thú cưng.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_service_pet_id' => 3, 'booking_id' => 1, 'pet_id' => 2, 'service_id' => 4, 'employee_id' => 6, 'scheduled_at' => Carbon::parse('2026-05-26 15:00:00'), 'status' => 'SCHEDULED', 'notes' => 'Bella đã được xếp lịch cắt móng.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_service_pet_id' => 4, 'booking_id' => 2, 'pet_id' => 3, 'service_id' => 1, 'employee_id' => 6, 'scheduled_at' => Carbon::parse('2026-05-26 14:30:00'), 'status' => 'DONE', 'notes' => 'Lucky đã hoàn tất dịch vụ tắm theo cân nặng.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_service_pet_id' => 5, 'booking_id' => 2, 'pet_id' => 3, 'service_id' => 3, 'employee_id' => 6, 'scheduled_at' => Carbon::parse('2026-05-26 15:30:00'), 'status' => 'DONE', 'notes' => 'Lucky đã kiểm tra sức khỏe cơ bản.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_service_pet_id' => 6, 'booking_id' => 3, 'pet_id' => 5, 'service_id' => 1, 'employee_id' => 6, 'scheduled_at' => Carbon::parse('2026-05-26 13:30:00'), 'status' => 'SCHEDULED', 'notes' => 'Coco đã xếp lịch tắm, chưa trừ kho.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_service_pet_id' => 7, 'booking_id' => 4, 'pet_id' => 4, 'service_id' => 1, 'employee_id' => 9, 'scheduled_at' => Carbon::parse('2026-05-21 13:00:00'), 'status' => 'DONE', 'notes' => 'Max đã hoàn tất dịch vụ tắm.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_service_pet_id' => 8, 'booking_id' => 4, 'pet_id' => 4, 'service_id' => 2, 'employee_id' => 9, 'scheduled_at' => Carbon::parse('2026-05-22 09:00:00'), 'status' => 'DONE', 'notes' => 'Max đã grooming toàn diện.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_service_pet_id' => 9, 'booking_id' => 5, 'pet_id' => 6, 'service_id' => 4, 'employee_id' => 7, 'scheduled_at' => Carbon::parse('2026-05-26 13:00:00'), 'status' => 'SCHEDULED', 'notes' => 'Nori đã xếp lịch cắt móng.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_service_pet_id' => 10, 'booking_id' => 6, 'pet_id' => 7, 'service_id' => 1, 'employee_id' => 8, 'scheduled_at' => Carbon::parse('2026-05-26 14:00:00'), 'status' => 'DONE', 'notes' => 'Rocky đã hoàn tất dịch vụ tắm.', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}