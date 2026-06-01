<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [];
        $orderDetails = [];
        $payments = [];

        $orderId = 1001;
        $orderDetailId = 1001;
        $paymentId = 1001;

        $paymentMethods = [
            'CASH',
            'BANK_TRANSFER',
            'MOMO',
            'VNPAY',
            'ZALOPAY',
            'CARD',
            'EWALLET',
        ];

        $bookings = DB::table('booking')
            ->where('booking_id', '>=', 1001)
            ->orderBy('booking_id')
            ->get();

        $customers = DB::table('customer')
            ->join('users', 'users.id', '=', 'customer.user_id')
            ->select(
                'customer.customer_id',
                'customer.full_name',
                'customer.phone',
                'users.email'
            )
            ->get()
            ->keyBy('customer_id');

        $bookingRooms = DB::table('booking_room')
            ->join('room', 'room.room_id', '=', 'booking_room.room_id')
            ->join('type_room', 'type_room.type_room_id', '=', 'room.type_room_id')
            ->select(
                'booking_room.booking_room_id',
                'booking_room.booking_id',
                'room.room_number',
                'type_room.base_price_per_day'
            )
            ->get()
            ->groupBy('booking_id');

        $bookingServices = DB::table('booking_service_pet')
            ->join('services', 'services.service_id', '=', 'booking_service_pet.service_id')
            ->join('pet', 'pet.pet_id', '=', 'booking_service_pet.pet_id')
            ->select(
                'booking_service_pet.booking_service_pet_id',
                'booking_service_pet.booking_id',
                'booking_service_pet.status',
                'services.service_name',
                'services.base_price',
                'pet.pet_name',
                'pet.species',
                'pet.weight_kg'
            )
            ->get()
            ->groupBy('booking_id');

        $employeesByBranch = DB::table('employee')
            ->orderBy('employee_id')
            ->get()
            ->groupBy('branch_id');

        $usersByBranch = DB::table('employee')
            ->whereIn('position', ['MANAGER', 'RECEPTIONIST', 'GROOMER', 'OTHER'])
            ->orderBy('employee_id')
            ->get()
            ->groupBy('branch_id');

        foreach ($bookings as $index => $booking) {
            $customer = $customers[$booking->customer_id] ?? null;

            if (!$customer) {
                continue;
            }

            $createdAt = Carbon::parse($booking->created_at);
            $checkin = Carbon::parse($booking->checkin_expected_at);
            $checkout = Carbon::parse($booking->checkout_expected_at);
            $nights = max(1, $checkin->diffInDays($checkout));

            $isCancelled = $booking->status === 'CANCELLED';

            $paymentMethod = $paymentMethods[$index % count($paymentMethods)];

            $branchEmployees = $employeesByBranch[$booking->branch_id] ?? collect();
            $branchUsers = $usersByBranch[$booking->branch_id] ?? collect();

            $createdByEmp = $branchEmployees->isNotEmpty()
                ? $branchEmployees[$index % $branchEmployees->count()]->employee_id
                : null;

            $createdByUser = $branchUsers->isNotEmpty()
                ? $branchUsers[$index % $branchUsers->count()]->user_id
                : 1;

            $subtotal = 0;
            $currentDetails = [];

            foreach (($bookingRooms[$booking->booking_id] ?? collect()) as $roomRow) {
                $unitPrice = (float) $roomRow->base_price_per_day;

                $ymd = $checkin->format('Y-m-d');

                $isHoliday =
                    ($ymd >= '2025-12-24' && $ymd <= '2025-12-31') ||
                    ($ymd >= '2026-01-01' && $ymd <= '2026-01-03') ||
                    ($ymd >= '2026-02-10' && $ymd <= '2026-02-24') ||
                    ($ymd >= '2026-04-28' && $ymd <= '2026-05-05');

                if ($isHoliday) {
                    $unitPrice *= 1.2;
                } elseif ($checkin->isWeekend()) {
                    $unitPrice *= 1.1;
                }

                $unitPrice = round($unitPrice, 0);
                $lineTotal = $unitPrice * $nights;
                $subtotal += $lineTotal;

                $currentDetails[] = [
                    'order_detail_id' => $orderDetailId++,
                    'order_id' => $orderId,
                    'booking_room_id' => $roomRow->booking_room_id,
                    'booking_service_pet_id' => null,
                    'title' => 'Lưu trú phòng ' . $roomRow->room_number . ' (' . $nights . ' đêm)',
                    'quantity' => $nights,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }

            foreach (($bookingServices[$booking->booking_id] ?? collect()) as $serviceRow) {
                $unitPrice = (float) $serviceRow->base_price;

                $ymd = $checkin->format('Y-m-d');

                $isHoliday =
                    ($ymd >= '2025-12-24' && $ymd <= '2025-12-31') ||
                    ($ymd >= '2026-01-01' && $ymd <= '2026-01-03') ||
                    ($ymd >= '2026-02-10' && $ymd <= '2026-02-24') ||
                    ($ymd >= '2026-04-28' && $ymd <= '2026-05-05');

                if ($isHoliday) {
                    $unitPrice *= 1.1;
                }

                if ($serviceRow->species === 'DOG' && $serviceRow->weight_kg > 20) {
                    $unitPrice *= 1.3;
                } elseif ($serviceRow->species === 'DOG' && $serviceRow->weight_kg > 10) {
                    $unitPrice *= 1.15;
                }

                $unitPrice = round($unitPrice, 0);
                $lineTotal = $unitPrice;
                $subtotal += $lineTotal;

                $currentDetails[] = [
                    'order_detail_id' => $orderDetailId++,
                    'order_id' => $orderId,
                    'booking_room_id' => null,
                    'booking_service_pet_id' => $serviceRow->booking_service_pet_id,
                    'title' => $serviceRow->service_name . ' - ' . $serviceRow->pet_name,
                    'quantity' => 1,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }

            if (empty($currentDetails)) {
                continue;
            }

            if ($isCancelled) {
                $status = 'CANCELLED';
                $paymentStatus = 'FAILED';
                $paidAt = null;
                $discountAmount = 0;
                $grandTotal = $subtotal;
            } else {
                $status = ($index % 3 === 0) ? 'COMPLETED' : 'PAID';
                $paymentStatus = 'SUCCESS';
                $paidAt = $createdAt->copy()->addHours(1 + ($index % 6));

                if ($subtotal >= 500000 && $index % 8 === 0) {
                    $discountAmount = min(100000, round($subtotal * 0.08, 0));
                } else {
                    $discountAmount = 0;
                }

                $grandTotal = $subtotal - $discountAmount;
            }

            $orders[] = [
                'order_id' => $orderId,
                'customer_id' => $booking->customer_id,
                'branch_id' => $booking->branch_id,
                'booking_id' => $booking->booking_id,
                'created_by_emp' => $createdByEmp,
                'created_by_user_id' => $createdByUser,
                'coupon_id' => null,
                'payment_method' => $paymentMethod,
                'status' => $status,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'grand_total' => $grandTotal,
                'paid_at' => $paidAt,
                'customer_name' => $customer->full_name,
                'customer_phone' => $customer->phone,
                'customer_email' => $customer->email,
                'created_at' => $createdAt,
                'updated_at' => $paidAt ?? $createdAt,
            ];

            foreach ($currentDetails as $detail) {
                $orderDetails[] = $detail;
            }

            $payments[] = [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'payment_method' => $paymentMethod,
                'provider' => match ($paymentMethod) {
                    'CASH' => 'Quầy thu ngân',
                    'BANK_TRANSFER' => 'Ngân hàng demo',
                    'MOMO' => 'Ví MoMo',
                    'VNPAY' => 'Cổng VNPay',
                    'ZALOPAY' => 'Ví ZaloPay',
                    'CARD' => 'Cổng thẻ demo',
                    'EWALLET' => 'Ví điện tử demo',
                    default => 'Khác',
                },
                'amount' => max(10000, $grandTotal),
                'status' => $paymentStatus,
                'paid_at' => $paymentStatus === 'SUCCESS' ? $paidAt : null,
                'note' => $paymentStatus === 'SUCCESS'
                    ? 'Thanh toán thành công cho đơn demo.'
                    : 'Thanh toán thất bại/hủy cho đơn demo.',
                'created_at' => $createdAt,
                'updated_at' => $paidAt ?? $createdAt,
            ];

            $orderId++;
            $paymentId++;
        }

        foreach (array_chunk($orders, 200) as $chunk) {
            DB::table('orders')->insert($chunk);
        }

        foreach (array_chunk($orderDetails, 200) as $chunk) {
            DB::table('order_details')->insert($chunk);
        }

        foreach (array_chunk($payments, 200) as $chunk) {
            DB::table('payments')->insert($chunk);
        }
    }
}