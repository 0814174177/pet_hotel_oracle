<?php

namespace App\Services\Booking;

use App\Models\Customer;
use App\Models\Pet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class BookingPetEligibilityService
{
    private const ROOM_HOLDING_STATUSES = [
        'PENDING',
        'PENDING_PAYMENT',
        'HOLDING',
        'CONFIRMED',
        'CHECKED_IN',
    ];

    public function petsCanBeBookedBy(?User $user, array $petIds): bool
    {
        if (! $user || $petIds === []) {
            return false;
        }

        $uniquePetIds = collect($petIds)->map(fn ($petId): int => (int) $petId)->unique()->values();
        $pets = Pet::whereIn('pet_id', $uniquePetIds)->get();

        if ($pets->count() !== $uniquePetIds->count()) {
            return false;
        }

        return $pets->every(fn (Pet $pet): bool => Gate::forUser($user)->allows('book', $pet));
    }

    public function petAvailabilityForUser(
        ?User $user,
        ?string $checkIn = null,
        ?string $checkOut = null
    ): array {
        $customer = $this->customerForUser($user);

        if (! $customer) {
            return [];
        }

        $pets = Pet::where('customer_id', $customer->customer_id)
            ->orderBy('pet_name')
            ->get();

        if ($pets->isEmpty()) {
            return [];
        }

        $hasDateRange = filled($checkIn) && filled($checkOut);
        $conflicts = $hasDateRange
            ? $this->petConflictsForDateRange(
                $pets->pluck('pet_id')->map(fn ($petId): int => (int) $petId)->all(),
                (string) $checkIn,
                (string) $checkOut
            )
            : collect();

        return $pets
            ->map(function (Pet $pet) use ($conflicts, $hasDateRange): array {
                $isInRoom = $this->petIsCurrentlyInRoom($pet);
                $conflict = $hasDateRange ? $conflicts->get((string) $pet->pet_id) : null;
                $isBooked = $conflict !== null;

                return [
                    'id' => (string) $pet->pet_id,
                    'pet_id' => (int) $pet->pet_id,
                    'is_available' => ! $isInRoom && ! $isBooked,
                    'is_in_room' => $isInRoom,
                    'is_booked' => $isBooked,
                    'message' => $isInRoom
                        ? 'Thú cưng này đang ở trong phòng khác.'
                        : ($isBooked ? $this->petConflictDisplayMessage($conflict) : ''),
                ];
            })
            ->values()
            ->all();
    }

    public function petBookingConflictMessage(array $petIds, string $checkin, string $checkout): ?string
    {
        $uniquePetIds = collect($petIds)
            ->map(fn ($petId): int => (int) $petId)
            ->unique()
            ->values();

        if ($uniquePetIds->isEmpty()) {
            return null;
        }

        $currentRoomConflicts = DB::table('booking_room_pet')
            ->join('booking_room', 'booking_room_pet.booking_room_id', '=', 'booking_room.booking_room_id')
            ->join('booking', 'booking_room.booking_id', '=', 'booking.booking_id')
            ->join('pet', 'booking_room_pet.pet_id', '=', 'pet.pet_id')
            ->whereIn('booking_room_pet.pet_id', $uniquePetIds->all())
            ->where('booking.status', 'CHECKED_IN')
            ->whereNull('booking.checkout_actual_at')
            ->select(['pet.pet_id', 'pet.pet_name'])
            ->orderBy('pet.pet_name')
            ->get()
            ->unique('pet_id')
            ->values();

        if ($currentRoomConflicts->isNotEmpty()) {
            $petNames = $currentRoomConflicts
                ->map(fn ($conflict): string => $conflict->pet_name ?: 'Thú cưng #'.$conflict->pet_id)
                ->implode(', ');

            return sprintf(
                '%s đang ở trong phòng khác. Vui lòng checkout thú cưng trước khi thêm vào booking mới.',
                $petNames
            );
        }

        $checkinAt = Carbon::parse($checkin);
        $checkoutAt = Carbon::parse($checkout);

        $conflicts = DB::table('booking_room_pet')
            ->join('booking_room', 'booking_room_pet.booking_room_id', '=', 'booking_room.booking_room_id')
            ->join('booking', 'booking_room.booking_id', '=', 'booking.booking_id')
            ->join('pet', 'booking_room_pet.pet_id', '=', 'pet.pet_id')
            ->whereIn('booking_room_pet.pet_id', $uniquePetIds->all())
            ->whereIn('booking.status', self::ROOM_HOLDING_STATUSES)
            ->where('booking.checkin_expected_at', '<', $checkoutAt->toDateTimeString())
            ->where('booking.checkout_expected_at', '>', $checkinAt->toDateTimeString())
            ->select([
                'booking.booking_id',
                'booking.checkin_expected_at',
                'booking.checkout_expected_at',
                'pet.pet_id',
                'pet.pet_name',
            ])
            ->orderBy('booking.checkin_expected_at')
            ->get()
            ->unique('pet_id')
            ->values();

        if ($conflicts->isEmpty()) {
            return null;
        }

        $petNames = $conflicts
            ->map(fn ($conflict): string => $conflict->pet_name ?: 'Thú cưng #'.$conflict->pet_id)
            ->implode(', ');
        $firstConflict = $conflicts->first();
        $conflictRange = $this->formatConflictDateRange(
            $firstConflict->checkin_expected_at,
            $firstConflict->checkout_expected_at
        );

        return sprintf(
            '%s đang có booking khác trong khoảng %s. Vui lòng chọn thú cưng hoặc ngày lưu trú khác.',
            $petNames,
            $conflictRange
        );
    }

    public function customerPets(?Customer $customer): array
    {
        if (! $customer) {
            return [];
        }

        try {
            return Pet::where('customer_id', $customer->customer_id)
                ->orderBy('pet_name')
                ->get()
                ->map(fn (Pet $pet) => [
                    'id' => (string) $pet->pet_id,
                    'name' => $pet->pet_name,
                    'species' => $this->displaySpecies($pet->species),
                    'breed' => $pet->breed ?: 'Chưa cập nhật',
                    'sex' => $this->displaySex($pet->sex),
                    'weight' => $pet->weight_kg !== null ? (float) $pet->weight_kg : null,
                    'is_in_room' => $this->petIsCurrentlyInRoom($pet),
                    'room_status_message' => 'Thú cưng này đang ở trong phòng khác.',
                    'note' => $pet->special_notes ?: 'Không có ghi chú đặc biệt',
                ])
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    public function petIsCurrentlyInRoom(Pet $pet): bool
    {
        return DB::table('booking_room_pet')
            ->join('booking_room', 'booking_room_pet.booking_room_id', '=', 'booking_room.booking_room_id')
            ->join('booking', 'booking_room.booking_id', '=', 'booking.booking_id')
            ->where('booking_room_pet.pet_id', $pet->pet_id)
            ->where('booking.status', 'CHECKED_IN')
            ->whereNull('booking.checkout_actual_at')
            ->exists();
    }

    private function petConflictsForDateRange(array $petIds, string $checkin, string $checkout)
    {
        $uniquePetIds = collect($petIds)
            ->map(fn ($petId): int => (int) $petId)
            ->unique()
            ->values();

        if ($uniquePetIds->isEmpty()) {
            return collect();
        }

        $checkinAt = Carbon::parse($checkin)->startOfDay();
        $checkoutAt = Carbon::parse($checkout)->startOfDay();

        return DB::table('booking_room_pet')
            ->join('booking_room', 'booking_room_pet.booking_room_id', '=', 'booking_room.booking_room_id')
            ->join('booking', 'booking_room.booking_id', '=', 'booking.booking_id')
            ->leftJoin('branch', 'booking.branch_id', '=', 'branch.branch_id')
            ->whereIn('booking_room_pet.pet_id', $uniquePetIds->all())
            ->whereIn('booking.status', self::ROOM_HOLDING_STATUSES)
            ->where('booking.checkin_expected_at', '<', $checkoutAt->toDateTimeString())
            ->where('booking.checkout_expected_at', '>', $checkinAt->toDateTimeString())
            ->select([
                'booking.booking_id',
                'booking.branch_id',
                'branch.branch_name',
                'booking.checkin_expected_at',
                'booking.checkout_expected_at',
                'booking_room_pet.pet_id',
            ])
            ->orderBy('booking.checkin_expected_at')
            ->get()
            ->unique('pet_id')
            ->keyBy(fn ($conflict): string => (string) $conflict->pet_id);
    }

    private function petConflictDisplayMessage(object $conflict): string
    {
        $branchName = filled($conflict->branch_name ?? null)
            ? ' tại '.$conflict->branch_name
            : '';

        return sprintf(
            'Thú cưng này đã có booking #%s%s trong khoảng %s.',
            $conflict->booking_id,
            $branchName,
            $this->formatConflictDateRange(
                $conflict->checkin_expected_at,
                $conflict->checkout_expected_at
            )
        );
    }

    private function customerForUser(?User $user): ?Customer
    {
        if (! $user) {
            return null;
        }

        return $user->customer ?: Customer::where('user_id', $user->id)->first();
    }

    private function formatConflictDateRange(mixed $checkin, mixed $checkout): string
    {
        try {
            return Carbon::parse($checkin)->format('d/m/Y H:i')
                .' - '
                .Carbon::parse($checkout)->format('d/m/Y H:i');
        } catch (Throwable) {
            return 'thoi gian da dat';
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

    private function displaySex(?string $sex): string
    {
        return match (strtoupper((string) $sex)) {
            'MALE' => 'Đực',
            'FEMALE' => 'Cái',
            default => 'Chưa rõ',
        };
    }
}
