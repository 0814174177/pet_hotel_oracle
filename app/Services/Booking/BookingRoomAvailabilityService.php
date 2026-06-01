<?php

namespace App\Services\Booking;

use App\Models\BookingRoom;
use App\Models\Room;
use App\Models\TypeRoom;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookingRoomAvailabilityService
{
    private const ROOM_HOLDING_STATUSES = [
        'PENDING',
        'PENDING_PAYMENT',
        'HOLDING',
        'CONFIRMED',
        'CHECKED_IN',
    ];

    public function getRoomTypeAvailability(
        string|int $branchId,
        ?string $checkIn = null,
        ?string $checkOut = null
    ): array {
        $hasDateRange = filled($checkIn) && filled($checkOut);
        $busyRoomIds = $hasDateRange
            ? $this->busyRoomIdsForDateRange($branchId, (string) $checkIn, (string) $checkOut)
            : collect();

        return TypeRoom::where('is_active', 1)
            ->whereHas('rooms', fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('base_price_per_day')
            ->get()
            ->map(function (TypeRoom $typeRoom) use ($branchId, $busyRoomIds, $hasDateRange): array {
                $roomQuery = Room::where('branch_id', $branchId)
                    ->where('type_room_id', $typeRoom->type_room_id);

                $totalRooms = (clone $roomQuery)->count();

                $availableRoomsQuery = (clone $roomQuery);

                if ($hasDateRange) {
                    $availableRoomsQuery->where('status', '<>', 'MAINTENANCE');
                } else {
                    $availableRoomsQuery->where('status', 'AVAILABLE');
                }

                if ($hasDateRange && $busyRoomIds->isNotEmpty()) {
                    $availableRoomsQuery->whereNotIn('room_id', $busyRoomIds->all());
                }

                $availableRooms = $availableRoomsQuery->count();
                $maxPets = max(1, (int) $typeRoom->max_slot);
                $minWeight = $typeRoom->pet_weight_min_kg !== null
                    ? (float) $typeRoom->pet_weight_min_kg
                    : null;
                $maxWeight = $typeRoom->pet_weight_max_kg !== null
                    ? (float) $typeRoom->pet_weight_max_kg
                    : null;

                return [
                    'id' => (string) $typeRoom->type_room_id,
                    'type_room_id' => (int) $typeRoom->type_room_id,
                    'branch_id' => (int) $branchId,
                    'name' => $this->displayTypeRoomName($typeRoom),
                    'description' => $typeRoom->notes,
                    'detail' => $typeRoom->notes ?: sprintf(
                        'Phòng %s với sức chứa tối đa %d thú cưng.',
                        $this->displayTypeRoomName($typeRoom),
                        $maxPets
                    ),
                    'price' => (float) $typeRoom->base_price_per_day,
                    'max_pets' => $maxPets,
                    'maxPets' => $maxPets,
                    'min_weight' => $minWeight,
                    'minWeight' => $minWeight,
                    'max_weight' => $maxWeight,
                    'maxWeight' => $maxWeight,
                    'total_rooms' => $totalRooms,
                    'totalRooms' => $totalRooms,
                    'available_rooms' => $availableRooms,
                    'availableRooms' => $availableRooms,
                    'availableRoomsCount' => $availableRooms,
                    'availabilityText' => $availableRooms > 0 ? 'Còn chỗ' : 'Hết chỗ',
                    'iconClass' => $this->roomIconClass($typeRoom),
                    'is_sold_out' => $availableRooms === 0,
                ];
            })
            ->values()
            ->all();
    }

    public function roomTypeAvailable(string|int $branchId, string|int $typeRoomId): bool
    {
        return Room::where('branch_id', $branchId)
            ->where('type_room_id', $typeRoomId)
            ->where('status', '<>', 'MAINTENANCE')
            ->exists();
    }

    public function dateRangeAvailable(
        string|int $branchId,
        string|int $typeRoomId,
        string $checkin,
        string $checkout
    ): bool {
        $checkinDate = Carbon::parse($checkin)->startOfDay();
        $checkoutDate = Carbon::parse($checkout)->startOfDay();
        $today = Carbon::today();

        if ($checkinDate->lt($today) || $checkoutDate->lte($checkinDate)) {
            return false;
        }

        return $this->availableRoomCountForType($branchId, $typeRoomId, $checkin, $checkout) > 0;
    }

    public function bookingAvailability(string $branchId): array
    {
        return TypeRoom::where('is_active', 1)
            ->get()
            ->mapWithKeys(fn (TypeRoom $typeRoom): array => [
                (string) $typeRoom->type_room_id => [
                    'unavailable' => $this->fullyBookedDatesForType($branchId, $typeRoom->type_room_id),
                ],
            ])
            ->all();
    }

    private function busyRoomIdsForDateRange(string|int $branchId, string $checkIn, string $checkOut)
    {
        $checkInAt = Carbon::parse($checkIn)->startOfDay();
        $checkOutAt = Carbon::parse($checkOut)->startOfDay();

        return BookingRoom::query()
            ->whereHas('booking', function ($query) use ($branchId, $checkInAt, $checkOutAt): void {
                $query->where('branch_id', $branchId)
                    ->whereIn('status', self::ROOM_HOLDING_STATUSES)
                    ->where('checkin_expected_at', '<', $checkOutAt)
                    ->where('checkout_expected_at', '>', $checkInAt);
            })
            ->whereHas('room', fn ($query) => $query->where('branch_id', $branchId))
            ->pluck('room_id')
            ->unique()
            ->values();
    }

    private function availableRoomCountForType(
        string|int $branchId,
        string|int $typeRoomId,
        string $checkIn,
        string $checkOut
    ): int {
        $busyRoomIds = $this->busyRoomIdsForDateRange($branchId, $checkIn, $checkOut);

        return Room::where('branch_id', $branchId)
            ->where('type_room_id', $typeRoomId)
            ->where('status', '<>', 'MAINTENANCE')
            ->when($busyRoomIds->isNotEmpty(), fn ($query) => $query->whereNotIn('room_id', $busyRoomIds->all()))
            ->count();
    }

    private function fullyBookedDatesForType(string|int $branchId, string|int $typeRoomId): array
    {
        $totalPhysicalRooms = Room::where('branch_id', $branchId)
            ->where('type_room_id', $typeRoomId)
            ->where('status', '<>', 'MAINTENANCE')
            ->count();

        if ($totalPhysicalRooms === 0) {
            return [];
        }

        $bookedRoomsByDate = [];
        $today = Carbon::today();

        $activeBookingRooms = DB::table('booking_room')
            ->join('booking', 'booking_room.booking_id', '=', 'booking.booking_id')
            ->join('room', 'booking_room.room_id', '=', 'room.room_id')
            ->where('room.branch_id', $branchId)
            ->where('room.type_room_id', $typeRoomId)
            ->whereIn('booking.status', self::ROOM_HOLDING_STATUSES)
            ->whereDate('booking.checkout_expected_at', '>=', $today->toDateString())
            ->select(['booking_room.room_id', 'booking.checkin_expected_at', 'booking.checkout_expected_at'])
            ->get();

        foreach ($activeBookingRooms as $bookingRoom) {
            try {
                $start = Carbon::parse($bookingRoom->checkin_expected_at)->startOfDay();
                $end = Carbon::parse($bookingRoom->checkout_expected_at)->startOfDay();
            } catch (Throwable) {
                continue;
            }

            if ($start->lt($today)) {
                $start = $today->copy();
            }

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $bookedRoomsByDate[$date->toDateString()][(string) $bookingRoom->room_id] = true;
            }
        }

        return collect($bookedRoomsByDate)
            ->filter(fn (array $roomIds): bool => count($roomIds) >= $totalPhysicalRooms)
            ->keys()
            ->sort()
            ->values()
            ->all();
    }

    private function roomIconClass(TypeRoom $typeRoom): string
    {
        return match ((int) $typeRoom->type_room_id % 3) {
            2 => 'yellow',
            0 => 'purple',
            default => 'gray',
        };
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
