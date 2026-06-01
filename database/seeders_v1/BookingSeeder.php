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

        $branches = DB::table('branch')
            ->where('is_active', 1)
            ->orderBy('branch_id')
            ->pluck('branch_id')
            ->values()
            ->all();

        $customers = DB::table('customer')
            ->where('customer_id', '>=', 1001)
            ->orderBy('customer_id')
            ->get();

        $pets = DB::table('pet')
            ->where('pet_id', '>=', 1001)
            ->orderBy('pet_id')
            ->get();

        $services = DB::table('services')
            ->where('is_active', 1)
            ->orderBy('service_id')
            ->get();

        $rooms = DB::table('room')
            ->where('status', 'AVAILABLE')
            ->orderBy('branch_id')
            ->orderBy('room_id')
            ->get();

        $employees = DB::table('employee')
            ->orderBy('employee_id')
            ->get();

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

        $typeRoomPrices = DB::table('type_room')
            ->pluck('base_price_per_day', 'type_room_id')
            ->all();

        $allDates = [];

        foreach ($monthTargets as $month => $target) {
            $monthStart = Carbon::parse($month . '-01 09:00:00');
            $daysInMonth = $monthStart->daysInMonth;
            $weightedDays = [];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $monthStart->copy()->day($day);

                $weight = 2;

                if ($date->isWeekend()) {
                    $weight += 2;
                }

                $ymd = $date->format('Y-m-d');

                if (
                    ($ymd >= '2025-12-24' && $ymd <= '2025-12-31') ||
                    ($ymd >= '2026-01-01' && $ymd <= '2026-01-03') ||
                    ($ymd >= '2026-02-10' && $ymd <= '2026-02-24') ||
                    ($ymd >= '2026-04-28' && $ymd <= '2026-05-05')
                ) {
                    $weight += $month === '2026-02' ? 9 : 6;
                }

                for ($i = 0; $i < $weight; $i++) {
                    $weightedDays[] = $date->copy();
                }
            }

            for ($i = 0; $i < $target; $i++) {
                $date = $weightedDays[($i * 7 + intdiv($i, 3)) % count($weightedDays)]->copy();
                $allDates[] = $date->setTime(8 + ($i % 11), ($i * 13) % 60, 0);
            }
        }

        usort($allDates, fn ($a, $b) => $a->timestamp <=> $b->timestamp);

        foreach ($allDates as $index => $date) {
            $branchId = $branches[$index % count($branches)];

            $branchCustomers = $customers->filter(function ($customer) use ($index, $customers) {
                return true;
            })->values();

            $customer = $branchCustomers[$index % $branchCustomers->count()];
            $customerPets = $petsByCustomer[$customer->customer_id] ?? [];

            if (empty($customerPets)) {
                continue;
            }

            $pet = $customerPets[$index % count($customerPets)];

            $ymd = $date->format('Y-m-d');
            $isHoliday =
                ($ymd >= '2025-12-24' && $ymd <= '2025-12-31') ||
                ($ymd >= '2026-01-01' && $ymd <= '2026-01-03') ||
                ($ymd >= '2026-02-10' && $ymd <= '2026-02-24') ||
                ($ymd >= '2026-04-28' && $ymd <= '2026-05-05');

            $isCancelled = ($index % 10 === 9);

            if ($isCancelled) {
                $bookingStatus = 'CANCELLED';
            } else {
                $bookingStatus = ($index % 12 === 0) ? 'CHECKED_OUT' : 'COMPLETED';
            }

            $roll = $index % 100;

            if ($isHoliday) {
                $bookingType = $roll < 70 ? 'ROOM' : ($roll < 90 ? 'ROOM_SERVICE' : 'SERVICE');
            } else {
                $bookingType = $roll < 45 ? 'ROOM' : ($roll < 65 ? 'ROOM_SERVICE' : 'SERVICE');
            }

            $needsRoom = $bookingType === 'ROOM' || $bookingType === 'ROOM_SERVICE';

            $checkin = $date->copy();
            $nights = 1;

            if ($needsRoom) {
                if ($isHoliday && $date->format('Y-m') === '2026-02') {
                    $nights = 4 + ($index % 4);
                } elseif ($isHoliday) {
                    $nights = 3 + ($index % 4);
                } elseif ($date->isWeekend()) {
                    $nights = 2 + ($index % 2);
                } else {
                    $nights = 1 + ($index % 2);
                }
            }

            $checkout = $checkin->copy()->addDays($nights)->setTime(11, 0, 0);

            $createdAt = $checkin->copy()->subDays(1 + ($index % 10))->setTime(8 + ($index % 8), ($index * 9) % 60, 0);

            $totalAmount = 0;
            $currentBookingRoomId = null;

            $bookingRows[] = [
                'booking_id' => $bookingId,
                'customer_id' => $customer->customer_id,
                'branch_id' => $branchId,
                'checkin_expected_at' => $checkin,
                'checkout_expected_at' => $checkout,
                'checkin_actual_at' => $isCancelled ? null : $checkin->copy()->addMinutes(5 + ($index % 25)),
                'checkout_actual_at' => $isCancelled ? null : $checkout->copy()->subMinutes(10 + ($index % 35)),
                'status' => $bookingStatus,
                'total_amount' => 0,
                'special_notes' => $isHoliday
                    ? 'Booking demo giai đoạn lễ/Tết, nhu cầu gửi pet tăng cao.'
                    : 'Booking demo ngày thường, dùng cho thống kê báo cáo.',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if ($needsRoom) {
                $availableRooms = $roomsByBranch[$branchId] ?? [];

                if (!empty($availableRooms)) {
                    $roomCandidates = [];

                    foreach ($availableRooms as $room) {
                        if ($pet->species === 'DOG' && str_contains($room->room_number, '-D')) {
                            $roomCandidates[] = $room;
                        }

                        if ($pet->species === 'CAT' && str_contains($room->room_number, '-C')) {
                            $roomCandidates[] = $room;
                        }
                    }

                    if (empty($roomCandidates)) {
                        $roomCandidates = $availableRooms;
                    }

                    $room = $roomCandidates[$index % count($roomCandidates)];
                    $roomPrice = (float) ($typeRoomPrices[$room->type_room_id] ?? 150000);

                    if ($isHoliday) {
                        $roomPrice *= 1.2;
                    } elseif ($date->isWeekend()) {
                        $roomPrice *= 1.1;
                    }

                    $roomPrice = round($roomPrice, 0);
                    $totalAmount += $roomPrice * $nights;

                    $currentBookingRoomId = $bookingRoomId;

                    $bookingRoomRows[] = [
                        'booking_room_id' => $bookingRoomId,
                        'booking_id' => $bookingId,
                        'room_id' => $room->room_id,
                        'assigned_at' => $createdAt,
                        'notes' => 'Gán phòng ' . $room->room_number . ' cho pet ' . $pet->pet_name . ' trong ' . $nights . ' đêm.',
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];

                    $bookingRoomPetRows[] = [
                        'booking_room_pet_id' => $bookingRoomPetId,
                        'booking_room_id' => $bookingRoomId,
                        'pet_id' => $pet->pet_id,
                        'notes' => 'Pet được gán vào phòng theo booking demo.',
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];

                    $bookingRoomId++;
                    $bookingRoomPetId++;
                }
            }

            if ($bookingType === 'SERVICE' || $bookingType === 'ROOM_SERVICE') {
                $serviceCount = $bookingType === 'ROOM_SERVICE' ? 1 + ($index % 2) : 1;
            } else {
                $serviceCount = ($index % 5 === 0) ? 1 : 0;
            }

            for ($s = 0; $s < $serviceCount; $s++) {
                $serviceCandidates = [];

                foreach ($services as $service) {
                    if ($service->species === 'ALL' || $service->species === $pet->species) {
                        $serviceCandidates[] = $service;
                    }
                }

                if (empty($serviceCandidates)) {
                    $serviceCandidates = $services->all();
                }

                $service = $serviceCandidates[($index + $s) % count($serviceCandidates)];

                $employeeId = null;
                $branchEmployees = $employeesByBranch[$branchId] ?? [];
                if (!empty($branchEmployees)) {
                    $employeeId = $branchEmployees[($index + $s) % count($branchEmployees)]->employee_id;
                }

                $servicePrice = (float) $service->base_price;

                if ($isHoliday) {
                    $servicePrice *= 1.1;
                }

                if ($pet->species === 'DOG' && $pet->weight_kg > 20) {
                    $servicePrice *= 1.3;
                } elseif ($pet->species === 'DOG' && $pet->weight_kg > 10) {
                    $servicePrice *= 1.15;
                }

                $servicePrice = round($servicePrice, 0);
                $totalAmount += $servicePrice;

                $bookingServicePetRows[] = [
                    'booking_service_pet_id' => $bookingServicePetId,
                    'booking_id' => $bookingId,
                    'pet_id' => $pet->pet_id,
                    'service_id' => $service->service_id,
                    'employee_id' => $employeeId,
                    'scheduled_at' => $checkin->copy()->addHours(2 + $s),
                    'status' => $isCancelled ? 'CANCELLED' : 'DONE',
                    'notes' => $isHoliday
                        ? 'Dịch vụ trong giai đoạn lễ/Tết.'
                        : 'Dịch vụ demo phục vụ thống kê.',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                $bookingServicePetId++;
            }

            if ($totalAmount <= 0) {
                $totalAmount = 150000;
            }

            $bookingRows[count($bookingRows) - 1]['total_amount'] = $isCancelled ? 0 : $totalAmount;

            $bookingId++;
        }

        foreach (array_chunk($bookingRows, 200) as $chunk) {
            DB::table('booking')->insert($chunk);
        }

        foreach (array_chunk($bookingRoomRows, 200) as $chunk) {
            DB::table('booking_room')->insert($chunk);
        }

        foreach (array_chunk($bookingRoomPetRows, 200) as $chunk) {
            DB::table('booking_room_pet')->insert($chunk);
        }

        foreach (array_chunk($bookingServicePetRows, 200) as $chunk) {
            DB::table('booking_service_pet')->insert($chunk);
        }
    }
}