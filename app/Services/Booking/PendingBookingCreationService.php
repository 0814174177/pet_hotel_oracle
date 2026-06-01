<?php

namespace App\Services\Booking;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\BookingRoomPet;
use App\Models\BookingServicePet;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Room;
use App\Models\Service;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PendingBookingCreationService
{
    private const ROOM_HOLDING_STATUSES = [
        'PENDING',
        'PENDING_PAYMENT',
        'HOLDING',
        'CONFIRMED',
        'CHECKED_IN',
    ];

    public function __construct(
        private BookingRoomAvailabilityService $roomAvailability,
        private BookingPetEligibilityService $petEligibility,
        private BookingReadService $bookingRead,
        private PendingBookingOrderService $pendingOrder
    ) {}

    public function createPendingBookingForUser(?User $user, array $bookingData): Booking
    {
        if (! $user) {
            throw new Exception('email|Vui lòng đăng nhập trước khi thanh toán.');
        }

        $customer = $this->customerForUser($user);

        if (! $customer) {
            throw new Exception('email|Vui lòng cập nhật thông tin khách hàng trước khi thanh toán.');
        }

        if (! $this->roomAvailability->roomTypeAvailable($bookingData['branch_id'], $bookingData['room_type'])) {
            throw new Exception('room_type|Loại phòng này hiện không còn phòng trống tại chi nhánh đã chọn.');
        }

        if (! $this->petEligibility->petsCanBeBookedBy($user, $bookingData['pet_ids'])) {
            throw new Exception('pet_ids|Bạn chỉ có thể đặt phòng cho thú cưng thuộc tài khoản của mình.');
        }

        if ($message = $this->petEligibility->petBookingConflictMessage(
            $bookingData['pet_ids'],
            $bookingData['checkin_expected_at'],
            $bookingData['checkout_expected_at']
        )) {
            throw new Exception('pet_ids|'.$message);
        }

        if (! $this->roomAvailability->dateRangeAvailable(
            $bookingData['branch_id'],
            $bookingData['room_type'],
            $bookingData['checkin_expected_at'],
            $bookingData['checkout_expected_at']
        )) {
            throw new Exception('checkin_expected_at|Khoảng thời gian này không còn phòng trống. Vui lòng chọn ngày khác.');
        }

        return $this->assignRoomAndServicesWithLock([
            ...$bookingData,
            'customer_id' => $customer->customer_id,
            'employee_id' => null,
            'user_id' => $user->id,
        ]);
    }

    public function assignRoomAndServicesWithLock(array $data): Booking
    {
        return DB::transaction(function () use ($data): Booking {
            try {
                $pets = Pet::whereIn('pet_id', $data['pet_ids'] ?? [$data['pet_id']])
                    ->lockForUpdate()
                    ->get();

                if ($message = $this->petEligibility->petBookingConflictMessage(
                    $data['pet_ids'] ?? [$data['pet_id']],
                    $data['checkin_expected_at'],
                    $data['checkout_expected_at']
                )) {
                    throw new Exception('pet_ids|'.$message);
                }

                $room = Room::where('branch_id', $data['branch_id'])
                    ->where('type_room_id', $data['room_type'])
                    ->where('status', '<>', 'MAINTENANCE')
                    ->whereDoesntHave('bookingRooms.booking', function ($query) use ($data): void {
                        $query->whereIn('status', self::ROOM_HOLDING_STATUSES)
                            ->where('checkin_expected_at', '<', $data['checkout_expected_at'])
                            ->where('checkout_expected_at', '>', $data['checkin_expected_at']);
                    })
                    ->inRandomOrder()
                    ->lockForUpdate()
                    ->first();

                if (! $room) {
                    throw new Exception('NO_ROOM|Không tìm thấy phòng trống phù hợp tại chi nhánh đã chọn.');
                }

                $this->assertPetsFitRoomType($pets, $room);
                $this->holdRoomForPendingBooking($room);

                $booking = Booking::create([
                    'customer_id' => $data['customer_id'],
                    'branch_id' => $data['branch_id'],
                    'checkin_expected_at' => $data['checkin_expected_at'],
                    'checkout_expected_at' => $data['checkout_expected_at'],
                    'status' => 'PENDING',
                ]);

                $bookingRoom = BookingRoom::create([
                    'booking_id' => $booking->booking_id,
                    'room_id' => $room->room_id,
                    'assigned_at' => now(),
                ]);

                foreach ($data['pet_ids'] ?? [$data['pet_id']] as $petId) {
                    BookingRoomPet::create([
                        'booking_room_id' => $bookingRoom->booking_room_id,
                        'pet_id' => $petId,
                    ]);
                }

                $serviceTotal = 0.0;
                $servicePetIds = $data['service_pet_ids'] ?? [];
                $serviceIds = collect($servicePetIds)
                    ->flatten()
                    ->unique()
                    ->values()
                    ->all();

                $services = Service::whereIn('service_id', $serviceIds)
                    ->get()
                    ->keyBy('service_id');

                foreach ($servicePetIds as $petId => $petServiceIds) {
                    foreach ($petServiceIds as $serviceId) {
                        $service = $services->get($serviceId);
                        $serviceTotal += (float) ($service?->base_price ?? 0);

                        BookingServicePet::create([
                            'booking_id' => $booking->booking_id,
                            'pet_id' => $petId,
                            'service_id' => $serviceId,
                            'employee_id' => $data['employee_id'] ?? null,
                            'scheduled_at' => $data['checkin_expected_at'],
                            'status' => 'PENDING',
                        ]);
                    }
                }

                $booking->update([
                    'total_amount' => $this->pendingOrder->estimatedBookingTotal($room, $booking, $serviceTotal),
                ]);

                $this->pendingOrder->createPendingOrderAndPayment(
                    $booking->fresh($this->bookingRead->bookingRelations()),
                    $data['user_id'] ?? null
                );
                $this->writeBookingAuditLog($booking, $data['user_id'] ?? null);

                return $booking->load($this->bookingRead->bookingRelations());
            } catch (QueryException $e) {
                Log::error('Database Error in Booking: '.$e->getMessage(), [
                    'branch_id' => $data['branch_id'] ?? null,
                    'room_type' => $data['room_type'] ?? null,
                    'customer_id' => $data['customer_id'] ?? null,
                ]);

                throw new Exception('DB_ERROR|Đã xảy ra lỗi hệ thống khi lưu thông tin. Vui lòng thử lại.');
            }
        });
    }

    private function assertPetsFitRoomType($pets, Room $room): void
    {
        $typeRoom = $room->typeRoom;

        if (! $typeRoom) {
            throw new Exception('room_type|Loại phòng đang không khả dụng. Vui lòng chọn phòng khác.');
        }

        $maxSlot = max(1, (int) $typeRoom->max_slot);

        if ($pets->count() > $maxSlot) {
            throw new Exception(sprintf(
                'pet_ids|Phòng %s chỉ nhận tối đa %d thú cưng. Vui lòng bớt số lượng thú cưng hoặc chọn loại phòng khác.',
                $typeRoom->type_name ?: 'đã chọn',
                $maxSlot
            ));
        }

        $minWeight = $typeRoom->pet_weight_min_kg !== null ? (float) $typeRoom->pet_weight_min_kg : null;
        $maxWeight = $typeRoom->pet_weight_max_kg !== null ? (float) $typeRoom->pet_weight_max_kg : null;

        $missingWeightPets = $pets
            ->filter(fn (Pet $pet): bool => $pet->weight_kg === null)
            ->map(fn (Pet $pet): string => $pet->pet_name ?: 'Thú cưng #'.$pet->pet_id)
            ->values();

        if ($missingWeightPets->isNotEmpty()) {
            throw new Exception(sprintf(
                'pet_ids|%s chưa có thông tin cân nặng, vui lòng cập nhật trước khi chọn phòng.',
                $missingWeightPets->implode(', ')
            ));
        }

        if ($minWeight === null && $maxWeight === null) {
            return;
        }

        $invalidPets = $pets
            ->filter(function (Pet $pet) use ($minWeight, $maxWeight): bool {
                $weight = (float) $pet->weight_kg;

                return ($minWeight !== null && $weight < $minWeight)
                    || ($maxWeight !== null && $weight > $maxWeight);
            })
            ->map(fn (Pet $pet): string => sprintf(
                '%s (%s)',
                $pet->pet_name ?: 'Thú cưng #'.$pet->pet_id,
                $pet->weight_kg === null ? 'chưa có cân nặng' : ((float) $pet->weight_kg).'kg'
            ))
            ->values();

        if ($invalidPets->isEmpty()) {
            return;
        }

        $weightRange = match (true) {
            $minWeight !== null && $maxWeight !== null => sprintf('từ %skg đến %skg', $minWeight, $maxWeight),
            $minWeight !== null => sprintf('từ %skg trở lên', $minWeight),
            default => sprintf('không quá %skg', $maxWeight),
        };

        throw new Exception(sprintf(
            'pet_ids|%s không phù hợp với phòng %s. Phòng này chỉ nhận thú cưng %s.',
            $invalidPets->implode(', '),
            $typeRoom->type_name ?: 'đã chọn',
            $weightRange
        ));
    }

    private function holdRoomForPendingBooking(Room $room): void
    {
        if ($room->status === 'MAINTENANCE') {
            throw new Exception('room_type|Phòng đang bảo trì, vui lòng chọn phòng khác.');
        }

        if ($room->status !== 'IN_USE') {
            $room->update(['status' => 'IN_USE']);
        }
    }

    private function writeBookingAuditLog(Booking $booking, ?int $userId): void
    {
        try {
            AuditLog::create([
                'table_name' => 'booking',
                'action_type' => 'INSERT',
                'row_pk' => (string) $booking->booking_id,
                'detail_text' => 'Customer booking created with pending payment order.',
                'changed_by_user_id' => $userId,
                'changed_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Unable to write booking audit log: '.$e->getMessage(), [
                'booking_id' => $booking->booking_id,
            ]);
        }
    }

    private function customerForUser(?User $user): ?Customer
    {
        if (! $user) {
            return null;
        }

        return $user->customer ?: Customer::where('user_id', $user->id)->first();
    }
}
