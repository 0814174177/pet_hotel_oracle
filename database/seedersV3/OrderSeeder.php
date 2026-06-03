<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::parse('2026-05-25 08:00:00');
        $paidAt = Carbon::parse('2026-05-25 09:30:00');

        DB::table('orders')->insert([
            ['order_id' => 1, 'customer_id' => 1, 'branch_id' => 4, 'booking_id' => 1, 'created_by_emp' => 2, 'created_by_user_id' => 2, 'coupon_id' => 1, 'payment_method' => 'CASH', 'status' => 'PENDING', 'subtotal' => 400000, 'discount_amount' => 40000, 'grand_total' => 360000, 'paid_at' => null, 'customer_name' => 'Nguyễn Minh Ánh', 'customer_phone' => '0909000301', 'customer_email' => 'customer.small@pethotel.test', 'created_at' => $now, 'updated_at' => $now],
            ['order_id' => 2, 'customer_id' => 2, 'branch_id' => 4, 'booking_id' => 2, 'created_by_emp' => 2, 'created_by_user_id' => 2, 'coupon_id' => 1, 'payment_method' => 'BANK_TRANSFER', 'status' => 'PARTIAL', 'subtotal' => 670000, 'discount_amount' => 67000, 'grand_total' => 603000, 'paid_at' => null, 'customer_name' => 'Trần Gia Huy', 'customer_phone' => '0909000302', 'customer_email' => 'customer.medium@pethotel.test', 'created_at' => $now, 'updated_at' => $now],
            ['order_id' => 3, 'customer_id' => 4, 'branch_id' => 4, 'booking_id' => 3, 'created_by_emp' => 2, 'created_by_user_id' => 2, 'coupon_id' => null, 'payment_method' => 'EWALLET', 'status' => 'PENDING', 'subtotal' => 370000, 'discount_amount' => 0, 'grand_total' => 370000, 'paid_at' => null, 'customer_name' => 'Phạm Thu Trang', 'customer_phone' => '0909000304', 'customer_email' => 'customer.capacity@pethotel.test', 'created_at' => $now, 'updated_at' => $now],
            ['order_id' => 4, 'customer_id' => 3, 'branch_id' => 2, 'booking_id' => 4, 'created_by_emp' => 5, 'created_by_user_id' => 5, 'coupon_id' => 1, 'payment_method' => 'CARD', 'status' => 'COMPLETED', 'subtotal' => 1250000, 'discount_amount' => 100000, 'grand_total' => 1150000, 'paid_at' => $paidAt, 'customer_name' => 'Lê Hoàng Nam', 'customer_phone' => '0909000303', 'customer_email' => 'customer.large@pethotel.test', 'created_at' => $now, 'updated_at' => $now],
            ['order_id' => 5, 'customer_id' => 5, 'branch_id' => 1, 'booking_id' => 5, 'created_by_emp' => 3, 'created_by_user_id' => 3, 'coupon_id' => null, 'payment_method' => 'CASH', 'status' => 'PENDING', 'subtotal' => 200000, 'discount_amount' => 0, 'grand_total' => 200000, 'paid_at' => null, 'customer_name' => 'Võ Khánh Linh', 'customer_phone' => '0909000305', 'customer_email' => 'customer.north@pethotel.test', 'created_at' => $now, 'updated_at' => $now],
            ['order_id' => 6, 'customer_id' => 6, 'branch_id' => 3, 'booking_id' => 6, 'created_by_emp' => 4, 'created_by_user_id' => 4, 'coupon_id' => null, 'payment_method' => 'OTHER', 'status' => 'PENDING', 'subtotal' => 570000, 'discount_amount' => 0, 'grand_total' => 570000, 'paid_at' => null, 'customer_name' => 'Đặng Quốc Bảo', 'customer_phone' => '0909000306', 'customer_email' => 'customer.east@pethotel.test', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('order_details')->insert([
            ['order_detail_id' => 1, 'order_id' => 1, 'booking_room_id' => 1, 'booking_service_pet_id' => null, 'title' => 'Phòng GV-S101 (1 đêm)', 'quantity' => 1, 'unit_price' => 150000, 'line_total' => 150000, 'created_at' => $now, 'updated_at' => $now],
            ['order_detail_id' => 2, 'order_id' => 1, 'booking_room_id' => null, 'booking_service_pet_id' => 1, 'title' => 'Tắm thú cưng - Milo 5kg', 'quantity' => 1, 'unit_price' => 100000, 'line_total' => 100000, 'created_at' => $now, 'updated_at' => $now],
            ['order_detail_id' => 3, 'order_id' => 1, 'booking_room_id' => null, 'booking_service_pet_id' => 2, 'title' => 'Tắm thú cưng - Bella 8kg', 'quantity' => 1, 'unit_price' => 100000, 'line_total' => 100000, 'created_at' => $now, 'updated_at' => $now],
            ['order_detail_id' => 4, 'order_id' => 1, 'booking_room_id' => null, 'booking_service_pet_id' => 3, 'title' => 'Cắt móng cho Bella', 'quantity' => 1, 'unit_price' => 50000, 'line_total' => 50000, 'created_at' => $now, 'updated_at' => $now],

            ['order_detail_id' => 5, 'order_id' => 2, 'booking_room_id' => 2, 'booking_service_pet_id' => null, 'title' => 'Phòng GV-M201 (2 đêm)', 'quantity' => 2, 'unit_price' => 220000, 'line_total' => 440000, 'created_at' => $now, 'updated_at' => $now],
            ['order_detail_id' => 6, 'order_id' => 2, 'booking_room_id' => null, 'booking_service_pet_id' => 4, 'title' => 'Tắm thú cưng - Lucky 15kg', 'quantity' => 1, 'unit_price' => 150000, 'line_total' => 150000, 'created_at' => $now, 'updated_at' => $now],
            ['order_detail_id' => 7, 'order_id' => 2, 'booking_room_id' => null, 'booking_service_pet_id' => 5, 'title' => 'Kiểm tra sức khỏe cơ bản cho Lucky', 'quantity' => 1, 'unit_price' => 80000, 'line_total' => 80000, 'created_at' => $now, 'updated_at' => $now],

            ['order_detail_id' => 8, 'order_id' => 3, 'booking_room_id' => 3, 'booking_service_pet_id' => null, 'title' => 'Phòng GV-M202 (1 đêm)', 'quantity' => 1, 'unit_price' => 220000, 'line_total' => 220000, 'created_at' => $now, 'updated_at' => $now],
            ['order_detail_id' => 9, 'order_id' => 3, 'booking_room_id' => null, 'booking_service_pet_id' => 6, 'title' => 'Tắm thú cưng - Coco 18kg', 'quantity' => 1, 'unit_price' => 150000, 'line_total' => 150000, 'created_at' => $now, 'updated_at' => $now],

            ['order_detail_id' => 10, 'order_id' => 4, 'booking_room_id' => 4, 'booking_service_pet_id' => null, 'title' => 'Phòng TD-L301 (2 đêm)', 'quantity' => 2, 'unit_price' => 350000, 'line_total' => 700000, 'created_at' => $now, 'updated_at' => $now],
            ['order_detail_id' => 11, 'order_id' => 4, 'booking_room_id' => null, 'booking_service_pet_id' => 7, 'title' => 'Tắm thú cưng - Max 30kg', 'quantity' => 1, 'unit_price' => 300000, 'line_total' => 300000, 'created_at' => $now, 'updated_at' => $now],
            ['order_detail_id' => 12, 'order_id' => 4, 'booking_room_id' => null, 'booking_service_pet_id' => 8, 'title' => 'Grooming toàn diện cho Max', 'quantity' => 1, 'unit_price' => 250000, 'line_total' => 250000, 'created_at' => $now, 'updated_at' => $now],

            ['order_detail_id' => 13, 'order_id' => 5, 'booking_room_id' => 5, 'booking_service_pet_id' => null, 'title' => 'Phòng Q1-S101 (1 đêm)', 'quantity' => 1, 'unit_price' => 150000, 'line_total' => 150000, 'created_at' => $now, 'updated_at' => $now],
            ['order_detail_id' => 14, 'order_id' => 5, 'booking_room_id' => null, 'booking_service_pet_id' => 9, 'title' => 'Cắt móng cho Nori', 'quantity' => 1, 'unit_price' => 50000, 'line_total' => 50000, 'created_at' => $now, 'updated_at' => $now],

            ['order_detail_id' => 15, 'order_id' => 6, 'booking_room_id' => 6, 'booking_service_pet_id' => null, 'title' => 'Phòng Q7-L301 (1 đêm)', 'quantity' => 1, 'unit_price' => 350000, 'line_total' => 350000, 'created_at' => $now, 'updated_at' => $now],
            ['order_detail_id' => 16, 'order_id' => 6, 'booking_room_id' => null, 'booking_service_pet_id' => 10, 'title' => 'Tắm thú cưng - Rocky 28kg', 'quantity' => 1, 'unit_price' => 220000, 'line_total' => 220000, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('booking_coupon_log')->insert([
            ['booking_coupon_log_id' => 1, 'booking_id' => 1, 'coupon_id' => 1, 'applied_at' => Carbon::parse('2026-05-25 08:10:00'), 'notes' => 'Áp dụng DEMO10, giảm 40.000đ.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_coupon_log_id' => 2, 'booking_id' => 2, 'coupon_id' => 1, 'applied_at' => Carbon::parse('2026-05-25 08:20:00'), 'notes' => 'Áp dụng DEMO10, giảm 67.000đ.', 'created_at' => $now, 'updated_at' => $now],
            ['booking_coupon_log_id' => 3, 'booking_id' => 4, 'coupon_id' => 1, 'applied_at' => Carbon::parse('2026-05-25 08:30:00'), 'notes' => 'Áp dụng DEMO10, đạt mức giảm tối đa 100.000đ.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('payments')->insert([
            ['payment_id' => 1, 'order_id' => 1, 'payment_method' => 'CASH', 'provider' => 'Quầy thu ngân', 'amount' => 360000, 'status' => 'PENDING', 'paid_at' => null, 'note' => 'Thanh toán tiền mặt đang chờ khách xác nhận.', 'created_at' => $now, 'updated_at' => $now],
            ['payment_id' => 2, 'order_id' => 2, 'payment_method' => 'BANK_TRANSFER', 'provider' => 'Ngân hàng demo', 'amount' => 300000, 'status' => 'SUCCESS', 'paid_at' => $paidAt, 'note' => 'Thanh toán một phần cho booking phòng vừa.', 'created_at' => $now, 'updated_at' => $now],
            ['payment_id' => 3, 'order_id' => 3, 'payment_method' => 'EWALLET', 'provider' => 'Ví điện tử demo', 'amount' => 370000, 'status' => 'PENDING', 'paid_at' => null, 'note' => 'Thanh toán đang chờ xử lý.', 'created_at' => $now, 'updated_at' => $now],
            ['payment_id' => 4, 'order_id' => 4, 'payment_method' => 'CARD', 'provider' => 'Cổng thẻ demo', 'amount' => 1150000, 'status' => 'SUCCESS', 'paid_at' => $paidAt, 'note' => 'Thanh toán thành công toàn bộ.', 'created_at' => $now, 'updated_at' => $now],
            ['payment_id' => 5, 'order_id' => 5, 'payment_method' => 'CASH', 'provider' => 'Quầy thu ngân', 'amount' => 200000, 'status' => 'PENDING', 'paid_at' => null, 'note' => 'Chờ thanh toán tại quầy.', 'created_at' => $now, 'updated_at' => $now],
            ['payment_id' => 6, 'order_id' => 6, 'payment_method' => 'OTHER', 'provider' => 'Nhà cung cấp demo', 'amount' => 570000, 'status' => 'PENDING', 'paid_at' => null, 'note' => 'Thanh toán demo bằng phương thức khác.', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}