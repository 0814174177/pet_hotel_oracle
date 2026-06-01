<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Room;
use App\Models\TypeRoom;
use Carbon\Carbon;
use Throwable;

class PendingBookingOrderService
{
    public function createPendingOrderAndPayment(Booking $booking, ?int $userId): Order
    {
        $existingOrder = Order::where('booking_id', $booking->booking_id)
            ->lockForUpdate()
            ->first();

        if ($existingOrder) {
            if (! in_array((string) $existingOrder->status, ['COMPLETED', 'PAID'], true)
                && ! $existingOrder->details()->exists()) {
                $subtotal = $this->createOrderDetailsForBooking($existingOrder, $booking);

                $existingOrder->update([
                    'subtotal' => $subtotal,
                    'grand_total' => max(0, round($subtotal - (float) $existingOrder->discount_amount, 2)),
                ]);

                $booking->update(['total_amount' => $subtotal]);
            }

            $this->ensurePendingPaymentForOrder($existingOrder);

            return $existingOrder->load(['details', 'payment']);
        }

        $order = Order::create([
            'customer_id' => $booking->customer_id,
            'branch_id' => $booking->branch_id,
            'booking_id' => $booking->booking_id,
            'created_by_emp' => null,
            'created_by_user_id' => $userId ?? $booking->customer?->user_id,
            'coupon_id' => null,
            'payment_method' => 'CASH',
            'status' => 'PENDING',
            'subtotal' => 0,
            'discount_amount' => 0,
            'grand_total' => 0,
            'paid_at' => null,
            'customer_name' => $booking->customer?->full_name ?: $booking->customer?->user?->name,
            'customer_phone' => $booking->customer?->phone,
            'customer_email' => $booking->customer?->user?->email,
        ]);

        $subtotal = $this->createOrderDetailsForBooking($order, $booking);

        $order->update([
            'subtotal' => $subtotal,
            'grand_total' => $subtotal,
        ]);

        $booking->update(['total_amount' => $subtotal]);
        $this->ensurePendingPaymentForOrder($order);

        return $order->load(['details', 'payment']);
    }

    public function estimatedBookingTotal(Room $room, Booking $booking, float $serviceTotal = 0): float
    {
        $roomPrice = (float) ($room->typeRoom?->base_price_per_day ?? 0);

        return ($roomPrice * max(1, $this->bookingNights($booking))) + $serviceTotal;
    }

    private function createOrderDetailsForBooking(Order $order, Booking $booking): float
    {
        if ($order->details()->exists()) {
            return (float) $order->details()->sum('line_total');
        }

        $subtotal = 0.0;
        $nights = max(1, $this->bookingNights($booking));

        foreach ($booking->bookingRooms as $bookingRoom) {
            $room = $bookingRoom->room;
            $typeRoom = $room?->typeRoom;
            $unitPrice = (float) ($typeRoom?->base_price_per_day ?? 0);
            $lineTotal = round($unitPrice * $nights, 2);

            OrderDetail::create([
                'order_id' => $order->order_id,
                'booking_room_id' => $bookingRoom->booking_room_id,
                'booking_service_pet_id' => null,
                'title' => sprintf(
                    'Phòng %s (%d đêm)',
                    $typeRoom ? $this->displayTypeRoomName($typeRoom) : ($room?->room_number ?: 'đã đặt'),
                    $nights
                ),
                'quantity' => $nights,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);

            $subtotal += $lineTotal;
        }

        foreach ($booking->bookingServicePets as $bookingServicePet) {
            $service = $bookingServicePet->service;
            $petName = $bookingServicePet->pet?->pet_name;
            $unitPrice = (float) ($service?->base_price ?? 0);

            OrderDetail::create([
                'order_id' => $order->order_id,
                'booking_room_id' => null,
                'booking_service_pet_id' => $bookingServicePet->booking_service_pet_id,
                'title' => trim(($service?->service_name ?: 'Dịch vụ') . ($petName ? ' - '.$petName : '')),
                'quantity' => 1,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice,
            ]);

            $subtotal += $unitPrice;
        }

        return round($subtotal, 2);
    }

    private function ensurePendingPaymentForOrder(Order $order): void
    {
        $amount = (float) $order->grand_total;

        if ($amount <= 0 || in_array((string) $order->status, ['COMPLETED', 'PAID', 'CANCELLED', 'REFUNDED'], true)) {
            return;
        }

        Payment::updateOrCreate(
            ['order_id' => $order->order_id],
            [
                'payment_method' => $order->payment_method,
                'provider' => 'Quầy thu ngân',
                'amount' => $amount,
                'status' => 'PENDING',
                'paid_at' => null,
                'note' => 'Chờ thanh toán booking #'.$order->booking_id.'.',
            ]
        );
    }

    private function bookingNights(Booking $booking): int
    {
        try {
            return (int) Carbon::parse($booking->checkin_expected_at)
                ->startOfDay()
                ->diffInDays(Carbon::parse($booking->checkout_expected_at)->startOfDay());
        } catch (Throwable) {
            return 0;
        }
    }

    private function displayTypeRoomName(TypeRoom $typeRoom): string
    {
        return match ((int) $typeRoom->type_room_id) {
            1 => 'Phòng nhỏ',
            2 => 'Phòng vừa',
            3 => 'Phòng lớn',
            default => $typeRoom->type_name,
        };
    }
}
