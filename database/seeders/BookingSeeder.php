<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $bookingRows = [];
        $bookingRoomRows = [];
        $bookingRoomPetRows = [];
        $bookingServicePetRows = [];

        $bookingId = 1001;
        $bookingRoomId = 1001;
        $bookingRoomPetId = 1001;
        $bookingServicePetId = 1001;

        $monthTargets = [
            '2025-12' => 255,
            '2026-01' => 270,
            '2026-02' => 350,
            '2026-03' => 330,
            '2026-04' => 370,
            '2026-05' => 425,
        ];

        $branches = DB::table('branch')->where('is_active', 1)->orderBy('branch_id')->pluck('branch_id')->values()->all();
        $customers = DB::table('customer')->where('customer_id', '>=', 1001)->orderBy('customer_id')->get();
        $pets = DB::table('pet')->where('pet_id', '>=', 1001)->orderBy('pet_id')->get();
        $services = DB::table('services')->where('is_active', 1)->orderBy('service_id')->get();
        $rooms = DB::table('room')->where('status', 'AVAILABLE')->orderBy('branch_id')->orderBy('room_id')->get();
        $employees = DB::table('employee')->where('status', 1)->orderBy('employee_id')->get();
        $typeRooms = DB::table('type_room')->get()->keyBy('type_room_id');

        if ($customers->isEmpty() || $pets->isEmpty() || $services->isEmpty() || $rooms->isEmpty()) {
            throw new \RuntimeException('BookingSeeder cần có customer, pet, services và room trước.');
        }

        $petsByCustomer = [];
        foreach ($pets as $pet) {
            $petsByCustomer[$pet->customer_id][] = $pet;
        }

        $roomsByBranch = [];
        foreach ($rooms as $room) {
            $roomsByBranch[$room->branch_id][] = $room;
        }

        $employeesByBranch = [];
        foreach ($employees as $employee) {
            $employeesByBranch[$employee->branch_id][] = $employee;
        }

        $allDates = [];
        foreach ($monthTargets as $month => $target) {
            $monthStart = Carbon::parse($month . '-01 09:00:00');
            $daysInMonth = $monthStart->daysInMonth;
            $weightedDays = [];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $monthStart->copy()->day($day);
                $weight = 2;
                if ($date->isWeekend()) { $weight += 2; }

                $ymd = $date->format('Y-m-d');
                if ($this->isHolidaySeason($ymd)) {
                    $weight += $month === '2026-02' ? 9 : 6;
                }

                for ($i = 0; $i < $weight; $i++) {
                    $weightedDays[] = $date->copy();
                }
            }

            for ($i = 0; $i < $target; $i++) {
                $date = $weightedDays[($i * 7 + intdiv($i, 3)) % count($weightedDays)]->copy();
                $allDates[] = $date->setTime(7 + ($i % 12), ($i * 13) % 60, 0);
            }
        }

        usort($allDates, fn ($a, $b) => $a->timestamp <=> $b->timestamp);

        // Theo dõi lịch chiếm phòng để tránh cùng phòng bị đặt trùng thời gian.
        $roomOccupiedUntil = [];
        $roomRotation = [];
        $branchCustomerCursor = [];
        foreach ($branches as $branchId) {
            $roomRotation[$branchId] = 0;
            $branchCustomerCursor[$branchId] = 0;
        }

        foreach ($allDates as $index => $date) {
            $branchId = $branches[$index % count($branches)];
            $ymd = $date->format('Y-m-d');
            $isHoliday = $this->isHolidaySeason($ymd);

            $checkin = $date->copy();
            $createdAt = $checkin->copy()->subDays(1 + ($index % 10))->setTime(8 + ($index % 8), ($index * 9) % 60, 0);

            // Tỷ lệ hủy giảm còn khoảng 4%, rải đều theo chi nhánh để không có chi nhánh nào bị hủy tới 20%.
            $isCancelled = ($index % 25 === 24);
            $bookingStatus = $this->resolveBookingStatus($index, $checkin, $isCancelled);

            // Chỉ chọn khách đã tồn tại trước thời điểm tạo booking để tránh dữ liệu "khách chưa tạo đã đặt phòng".
            $eligibleCustomers = $customers->filter(function ($customer) use ($createdAt) {
                return Carbon::parse($customer->created_at)->lte($createdAt);
            })->values();
            if ($eligibleCustomers->isEmpty()) {
                throw new \RuntimeException(
                    'Không có customer được tạo trước thời điểm booking ' . $createdAt->toDateTimeString() . '.'
                );
            }

            $customer = $eligibleCustomers[$branchCustomerCursor[$branchId] % $eligibleCustomers->count()];
            $branchCustomerCursor[$branchId]++;
            $customerPets = $petsByCustomer[$customer->customer_id] ?? [];
            if (empty($customerPets)) { continue; }

            $pet = $customerPets[$index % count($customerPets)];

            $roll = $index % 100;
            if ($isHoliday) {
                $bookingType = $roll < 68 ? 'ROOM' : ($roll < 90 ? 'ROOM_SERVICE' : 'SERVICE');
            } else {
                $bookingType = $roll < 45 ? 'ROOM' : ($roll < 67 ? 'ROOM_SERVICE' : 'SERVICE');
            }

            $needsRoom = in_array($bookingType, ['ROOM', 'ROOM_SERVICE'], true);
            $nights = 1;
            if ($needsRoom) {
                if ($isHoliday && $date->format('Y-m') === '2026-02') {
                    $nights = 3 + ($index % 4); // Tết: 3-6 đêm.
                } elseif ($isHoliday) {
                    $nights = 2 + ($index % 4); // Lễ khác: 2-5 đêm.
                } elseif ($date->isWeekend()) {
                    $nights = 2 + ($index % 2);
                } else {
                    $nights = 1 + ($index % 2);
                }
            }
            $checkout = $checkin->copy()->addDays($nights)->setTime(11, 0, 0);

            $totalAmount = 0;
            $currentBookingRoomId = null;
            $assignedRoom = null;

            if ($needsRoom) {
                $assignedRoom = $this->findAvailableRoom(
                    $roomsByBranch[$branchId] ?? [],
                    $typeRooms,
                    $pet,
                    $checkin,
                    $checkout,
                    $roomOccupiedUntil,
                    $roomRotation[$branchId]
                );

                // Khi phòng thật sự hết vào cao điểm, chuyển sang booking dịch vụ trong ngày thay vì ép trùng phòng.
                if (!$assignedRoom) {
                    $bookingType = 'SERVICE';
                    $needsRoom = false;
                    $nights = 1;
                    $checkout = $checkin->copy()->addDay()->setTime(11, 0, 0);
                }
            }

            $actualTimes = $this->actualTimesByStatus($bookingStatus, $checkin, $checkout, $index);

            $bookingRows[] = [
                'booking_id' => $bookingId,
                'customer_id' => $customer->customer_id,
                'branch_id' => $branchId,
                'checkin_expected_at' => $checkin,
                'checkout_expected_at' => $checkout,
                'checkin_actual_at' => $actualTimes['checkin_actual_at'],
                'checkout_actual_at' => $actualTimes['checkout_actual_at'],
                'status' => $bookingStatus,
                'total_amount' => 0,
                'special_notes' => $isHoliday ? 'Booking demo giai đoạn lễ/Tết, nhu cầu gửi pet tăng cao.' : 'Booking demo ngày thường, dùng cho thống kê báo cáo.',
                'created_at' => $createdAt,
                'updated_at' => $actualTimes['updated_at'] ?? $createdAt,
            ];

            if ($assignedRoom) {
                $roomRotation[$branchId]++;
                $roomOccupiedUntil[$assignedRoom->room_id] = $checkout->copy();
                $roomType = $typeRooms[$assignedRoom->type_room_id] ?? null;
                $roomPrice = (float) ($roomType->base_price_per_day ?? 150000);
                if ($isHoliday) { $roomPrice *= 1.2; }
                elseif ($date->isWeekend()) { $roomPrice *= 1.1; }
                $roomPrice = round($roomPrice, 0);
                $totalAmount += $roomPrice * $nights;
                $currentBookingRoomId = $bookingRoomId;

                $bookingRoomRows[] = [
                    'booking_room_id' => $bookingRoomId,
                    'booking_id' => $bookingId,
                    'room_id' => $assignedRoom->room_id,
                    'assigned_at' => $createdAt,
                    'notes' => 'Gán phòng ' . $assignedRoom->room_number . ' cho pet ' . $pet->pet_name . ' trong ' . $nights . ' đêm. Không trùng lịch phòng.',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                $bookingRoomPetRows[] = [
                    'booking_room_pet_id' => $bookingRoomPetId++,
                    'booking_room_id' => $bookingRoomId,
                    'pet_id' => $pet->pet_id,
                    'notes' => 'Pet được gán vào phòng còn trống theo lịch seed.',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                // Thêm một số case 2 pet cùng chủ ở chung phòng để dữ liệu thể hiện đúng max_slot = 2.
                $secondPet = $this->findSecondPetForSameRoom($customerPets, $pet, $assignedRoom, $typeRooms);
                if ($secondPet && $index % 17 === 0) {
                    $bookingRoomPetRows[] = [
                        'booking_room_pet_id' => $bookingRoomPetId++,
                        'booking_room_id' => $bookingRoomId,
                        'pet_id' => $secondPet->pet_id,
                        'notes' => 'Pet thứ hai cùng chủ, ở chung phòng vì loại phòng cho phép tối đa 2 bé.',
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];
                }

                $bookingRoomId++;
            }

            if ($bookingType === 'SERVICE' || $bookingType === 'ROOM_SERVICE') {
                $serviceCount = $bookingType === 'ROOM_SERVICE' ? 1 + ($index % 2) : 1;
            } else {
                $serviceCount = ($index % 5 === 0) ? 1 : 0;
            }

            if (!$currentBookingRoomId && $serviceCount === 0) {
                $serviceCount = 1;
            }

            for ($s = 0; $s < $serviceCount; $s++) {
                $serviceCandidates = [];
                foreach ($services as $service) {
                    if ($service->species === 'ALL' || $service->species === $pet->species) {
                        $serviceCandidates[] = $service;
                    }
                }
                if (empty($serviceCandidates)) { $serviceCandidates = $services->all(); }
                $service = $serviceCandidates[($index + $s) % count($serviceCandidates)];

                $employeeId = null;
                $branchEmployees = $employeesByBranch[$branchId] ?? [];
                if (!empty($branchEmployees)) {
                    $employeeId = $branchEmployees[($index + $s) % count($branchEmployees)]->employee_id;
                }

                $servicePrice = (float) $service->base_price;
                if ($isHoliday) { $servicePrice *= 1.1; }
                if ($pet->species === 'DOG' && $pet->weight_kg > 20) { $servicePrice *= 1.3; }
                elseif ($pet->species === 'DOG' && $pet->weight_kg > 10) { $servicePrice *= 1.15; }
                $servicePrice = round($servicePrice, 0);
                $totalAmount += $servicePrice;

                $bookingServicePetRows[] = [
                    'booking_service_pet_id' => $bookingServicePetId++,
                    'booking_id' => $bookingId,
                    'pet_id' => $pet->pet_id,
                    'service_id' => $service->service_id,
                    'employee_id' => $employeeId,
                    'scheduled_at' => $checkin->copy()->addHours(2 + $s),
                    'status' => $this->serviceStatusByBookingStatus($bookingStatus),
                    'notes' => $isHoliday ? 'Dịch vụ trong giai đoạn lễ/Tết.' : 'Dịch vụ demo phục vụ thống kê.',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }

            $bookingRows[count($bookingRows) - 1]['total_amount'] = $isCancelled ? 0 : max(0, $totalAmount);
            $bookingId++;
        }

        foreach (array_chunk($bookingRows, 200) as $chunk) { DB::table('booking')->insert($chunk); }
        foreach (array_chunk($bookingRoomRows, 200) as $chunk) { DB::table('booking_room')->insert($chunk); }
        foreach (array_chunk($bookingRoomPetRows, 200) as $chunk) { DB::table('booking_room_pet')->insert($chunk); }
        foreach (array_chunk($bookingServicePetRows, 200) as $chunk) { DB::table('booking_service_pet')->insert($chunk); }
    }

    private function resolveBookingStatus(int $index, Carbon $checkin, bool $isCancelled): string
    {
        if ($isCancelled) { return 'CANCELLED'; }

        // Các booking cuối tháng 5 giữ lại một ít trạng thái active để demo luồng xác nhận/check-in.
        if ($checkin->gte(Carbon::parse('2026-05-24 00:00:00'))) {
            return match ($index % 10) {
                0, 1 => 'PENDING',
                2, 3, 4, 5 => 'CONFIRMED',
                6 => 'CHECKED_IN',
                7 => 'CHECKED_OUT',
                default => 'COMPLETED',
            };
        }

        return ($index % 12 === 0) ? 'CHECKED_OUT' : 'COMPLETED';
    }

    private function actualTimesByStatus(string $status, Carbon $checkin, Carbon $checkout, int $index): array
    {
        return match ($status) {
            'CANCELLED', 'PENDING', 'CONFIRMED' => [
                'checkin_actual_at' => null,
                'checkout_actual_at' => null,
                'updated_at' => $checkin->copy()->subHours(2),
            ],
            'CHECKED_IN' => [
                'checkin_actual_at' => $checkin->copy()->addMinutes(5 + ($index % 25)),
                'checkout_actual_at' => null,
                'updated_at' => $checkin->copy()->addMinutes(5 + ($index % 25)),
            ],
            default => [
                'checkin_actual_at' => $checkin->copy()->addMinutes(5 + ($index % 25)),
                'checkout_actual_at' => $checkout->copy()->subMinutes(10 + ($index % 35)),
                'updated_at' => $checkout->copy()->subMinutes(10 + ($index % 35)),
            ],
        };
    }

    private function serviceStatusByBookingStatus(string $bookingStatus): string
    {
        return match ($bookingStatus) {
            'CANCELLED' => 'CANCELLED',
            'PENDING', 'CONFIRMED', 'CHECKED_IN' => 'SCHEDULED',
            default => 'DONE',
        };
    }

    private function isHolidaySeason(string $ymd): bool
    {
        return ($ymd >= '2025-12-24' && $ymd <= '2025-12-31')
            || ($ymd >= '2026-01-01' && $ymd <= '2026-01-03')
            || ($ymd >= '2026-02-10' && $ymd <= '2026-02-24')
            || ($ymd >= '2026-04-28' && $ymd <= '2026-05-05');
    }

    private function findAvailableRoom(array $rooms, $typeRooms, object $pet, Carbon $checkin, Carbon $checkout, array $roomOccupiedUntil, int $rotation): ?object
    {
        if (empty($rooms)) { return null; }

        $candidates = [];
        foreach ($rooms as $room) {
            $roomType = $typeRooms[$room->type_room_id] ?? null;
            if (!$roomType) { continue; }

            $weight = (float) $pet->weight_kg;
            if ($weight < (float) $roomType->pet_weight_min_kg || $weight > (float) $roomType->pet_weight_max_kg) {
                continue;
            }

            $isDogRoom = str_contains($room->room_number, '-D');
            $isCatRoom = str_contains($room->room_number, '-C');
            if ($pet->species === 'DOG' && !$isDogRoom) { continue; }
            if ($pet->species === 'CAT' && !$isCatRoom) { continue; }
            if (!in_array($pet->species, ['DOG', 'CAT'], true) && !$isCatRoom) { continue; }

            $occupiedUntil = $roomOccupiedUntil[$room->room_id] ?? null;
            if ($occupiedUntil instanceof Carbon && $occupiedUntil->gt($checkin)) {
                continue;
            }

            $candidates[] = $room;
        }

        if (empty($candidates)) { return null; }
        return $candidates[$rotation % count($candidates)];
    }

    private function findSecondPetForSameRoom(array $customerPets, object $mainPet, object $room, $typeRooms): ?object
    {
        $roomType = $typeRooms[$room->type_room_id] ?? null;
        if (!$roomType || (int) $roomType->max_slot < 2) { return null; }

        $isDogRoom = str_contains($room->room_number, '-D');
        $isCatRoom = str_contains($room->room_number, '-C');

        foreach ($customerPets as $candidate) {
            if ($candidate->pet_id === $mainPet->pet_id) { continue; }
            if ($candidate->species !== $mainPet->species) { continue; }
            if ($candidate->species === 'DOG' && !$isDogRoom) { continue; }
            if ($candidate->species === 'CAT' && !$isCatRoom) { continue; }

            $weight = (float) $candidate->weight_kg;
            if ($weight >= (float) $roomType->pet_weight_min_kg && $weight <= (float) $roomType->pet_weight_max_kg) {
                return $candidate;
            }
        }

        return null;
    }
}
