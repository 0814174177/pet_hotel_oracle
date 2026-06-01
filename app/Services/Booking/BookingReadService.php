<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\BookingServicePet;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\TypeRoom;
use Carbon\Carbon;
use Throwable;

class BookingReadService
{
    public function bookingHistoryItems(Customer $customer): array
    {
        return Booking::with($this->bookingRelations())
            ->where('customer_id', $customer->customer_id)
            ->orderByDesc('checkin_expected_at')
            ->get()
            ->map(fn (Booking $booking): array => $this->bookingSummary($booking))
            ->values()
            ->all();
    }

    public function findCustomerBooking(Customer $customer, string $bookingId): ?Booking
    {
        return Booking::with($this->bookingRelations())
            ->where('customer_id', $customer->customer_id)
            ->where('booking_id', $bookingId)
            ->first();
    }

    public function bookingDetail(Booking $booking): array
    {
        $summary = $this->bookingSummary($booking);
        $status = $this->bookingDisplayStatus($booking);

        return [
            ...$summary,
            'status' => $status,
            'checkin' => $this->formatDateTime($booking->checkin_expected_at),
            'checkout' => $this->formatDateTime($booking->checkout_expected_at),
            'nights' => $this->bookingNights($booking),
            'branch' => [
                'name' => $booking->branch?->branch_name ?: 'Chi nhánh đang cập nhật',
                'address' => $booking->branch?->address ?: 'Đang cập nhật',
                'phone' => $booking->branch?->phone ?: 'Đang cập nhật',
            ],
            'rooms' => $this->bookingRoomsFor($booking),
            'pets' => $this->bookingPetsFor($booking),
            'services' => $this->bookingServicesFor($booking),
            'total_amount' => $this->bookingTotalAmount($booking),
            'note' => $booking->special_notes ?? null,
        ];
    }

    public function bookingRelations(): array
    {
        return [
            'branch',
            'bookingRooms.room.typeRoom',
            'bookingRooms.bookingRoomPets.pet',
            'bookingServicePets.service',
            'bookingServicePets.pet',
            'orders',
        ];
    }

    private function bookingSummary(Booking $booking): array
    {
        $status = strtoupper((string) $booking->status);
        $isPaid = $this->bookingHasCompletedOrder($booking);
        $statusMeta = $this->bookingStatusMeta($isPaid ? 'PAID' : $status);
        $petNames = $this->bookingPetNames($booking);
        $roomTypes = $this->bookingRoomTypesFor($booking);
        $roomLabel = $roomTypes ? implode(', ', $roomTypes) : 'Chưa phân phòng';

        return [
            'id' => (string) $booking->booking_id,
            'title' => sprintf(
                '%s - %s',
                $petNames ? implode(', ', $petNames) : 'Booking '.$booking->booking_id,
                $roomLabel
            ),
            'pet_count' => count($petNames),
            'date_range' => $this->formatDateRange($booking),
            'branch_name' => $booking->branch?->branch_name ?: 'Chi nhánh đang cập nhật',
            'status_label' => $statusMeta['label'],
            'status_class' => $statusMeta['class'],
            'icon_class' => $statusMeta['icon'],
            'group' => $this->bookingHistoryGroup($booking),
            'detail_url' => route('booking.show', $booking->booking_id),
            'payment_url' => url('/payment?booking_id='.$booking->booking_id),
            'show_payment' => ! $isPaid && in_array($status, ['PENDING', 'CONFIRMED'], true),
        ];
    }

    private function bookingHistoryGroup(Booking $booking): string
    {
        $status = strtoupper((string) $booking->status);

        if ($status === 'CANCELLED') {
            return 'cancelled';
        }

        if (in_array($status, ['CHECKED_OUT', 'COMPLETED'], true)) {
            return 'done';
        }

        return 'active';
    }

    private function bookingStatusMeta(string $status): array
    {
        return match ($status) {
            'PAID' => ['label' => 'Đã thanh toán', 'class' => 'status-paid', 'icon' => 'blue'],
            'CANCELLED' => ['label' => 'Đã hủy', 'class' => 'status-cancelled', 'icon' => 'red'],
            'CHECKED_OUT', 'COMPLETED' => ['label' => 'Đã thanh toán', 'class' => 'status-paid', 'icon' => 'blue'],
            'CHECKED_IN' => ['label' => 'Đang lưu trú', 'class' => 'status-pending', 'icon' => 'blue'],
            'CONFIRMED' => ['label' => 'Đã xác nhận', 'class' => 'status-pending', 'icon' => ''],
            default => ['label' => 'Đã giữ chỗ', 'class' => 'status-pending', 'icon' => ''],
        };
    }

    private function bookingDisplayStatus(Booking $booking): string
    {
        return $this->bookingHasCompletedOrder($booking)
            ? 'PAID'
            : strtoupper((string) $booking->status);
    }

    private function bookingHasCompletedOrder(Booking $booking): bool
    {
        return $booking->orders->contains(
            fn ($order): bool => in_array(strtoupper((string) $order->status), ['COMPLETED', 'PAID'], true)
        );
    }

    private function bookingPetNames(Booking $booking): array
    {
        $roomPets = $booking->bookingRooms
            ->flatMap(fn ($bookingRoom) => $bookingRoom->bookingRoomPets)
            ->map(fn ($bookingRoomPet) => $bookingRoomPet->pet?->pet_name)
            ->filter();

        $servicePets = $booking->bookingServicePets
            ->map(fn ($bookingServicePet) => $bookingServicePet->pet?->pet_name)
            ->filter();

        return $roomPets->merge($servicePets)->unique()->values()->all();
    }

    private function bookingRoomTypesFor(Booking $booking): array
    {
        return $booking->bookingRooms
            ->map(fn ($bookingRoom) => $bookingRoom->room?->typeRoom ? $this->displayTypeRoomName($bookingRoom->room->typeRoom) : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function bookingRoomsFor(Booking $booking): array
    {
        return $booking->bookingRooms
            ->map(fn ($bookingRoom) => [
                'room_number' => $bookingRoom->room?->room_number ?: 'Chưa phân phòng',
                'type' => $bookingRoom->room?->typeRoom ? $this->displayTypeRoomName($bookingRoom->room->typeRoom) : 'Chưa phân loại',
                'price' => (float) ($bookingRoom->room?->typeRoom?->base_price_per_day ?: 0),
                'assigned_at' => $this->formatDateTime($bookingRoom->assigned_at),
            ])
            ->values()
            ->all();
    }

    private function bookingPetsFor(Booking $booking): array
    {
        return $booking->bookingRooms
            ->flatMap(fn ($bookingRoom) => $bookingRoom->bookingRoomPets)
            ->map(fn ($bookingRoomPet) => $bookingRoomPet->pet)
            ->filter()
            ->unique('pet_id')
            ->map(fn (Pet $pet) => [
                'name' => $pet->pet_name,
                'species' => $this->displaySpecies($pet->species),
                'breed' => $pet->breed ?: 'Chưa cập nhật',
                'weight' => filled($pet->weight_kg) ? (float) $pet->weight_kg : null,
            ])
            ->values()
            ->all();
    }

    private function bookingServicesFor(Booking $booking): array
    {
        return $booking->bookingServicePets
            ->map(fn ($bookingServicePet) => [
                'name' => $bookingServicePet->service?->service_name ?: 'Dịch vụ đang cập nhật',
                'pet_name' => $bookingServicePet->pet?->pet_name ?: 'Thú cưng',
                'price' => (float) ($bookingServicePet->service?->base_price ?: 0),
                'status' => $bookingServicePet->status ?: 'PENDING',
            ])
            ->values()
            ->all();
    }

    private function bookingTotalAmount(Booking $booking): float
    {
        if (filled($booking->total_amount)) {
            return (float) $booking->total_amount;
        }

        $orderTotal = $booking->orders->sum(fn ($order) => (float) ($order->grand_total ?: 0));

        if ($orderTotal > 0) {
            return $orderTotal;
        }

        $nights = max(1, $this->bookingNights($booking));
        $roomTotal = $booking->bookingRooms->sum(
            fn (BookingRoom $bookingRoom): float => (float) ($bookingRoom->room?->typeRoom?->base_price_per_day ?: 0) * $nights
        );
        $serviceTotal = $booking->bookingServicePets->sum(
            fn (BookingServicePet $bookingServicePet): float => (float) ($bookingServicePet->service?->base_price ?: 0)
        );

        return $roomTotal + $serviceTotal;
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

    private function formatDateRange(Booking $booking): string
    {
        try {
            return Carbon::parse($booking->checkin_expected_at)->format('d/m/Y')
                .' - '
                .Carbon::parse($booking->checkout_expected_at)->format('d/m/Y');
        } catch (Throwable) {
            return 'Đang cập nhật';
        }
    }

    private function formatDateTime(mixed $value): string
    {
        try {
            return Carbon::parse($value)->format('d/m/Y H:i');
        } catch (Throwable) {
            return 'Đang cập nhật';
        }
    }

    private function displaySpecies(?string $species): string
    {
        return match (strtoupper((string) $species)) {
            'DOG' => 'Chó',
            'CAT' => 'Mèo',
            default => 'Khác',
        };
    }
}
