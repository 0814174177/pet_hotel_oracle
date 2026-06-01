<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeoplePetSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::parse('2026-05-25 08:00:00');

        DB::table('employee')->insert([
            ['employee_id' => 1, 'user_id' => 1, 'branch_id' => 4, 'full_name' => 'Admin Demo', 'position' => 'MANAGER', 'salary' => 22000000, 'phone' => '0909000200', 'hire_date' => '2025-05-25', 'birthday' => '1990-01-15', 'avatar' => null, 'experience' => '6 năm', 'notes' => 'Tài khoản quản trị demo.', 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 2, 'user_id' => 2, 'branch_id' => 4, 'full_name' => 'Quản lý Gò Vấp', 'position' => 'MANAGER', 'salary' => 19000000, 'phone' => '0909000210', 'hire_date' => '2025-08-01', 'birthday' => '1991-02-20', 'avatar' => null, 'experience' => '4 năm', 'notes' => 'Quản lý chi nhánh Gò Vấp.', 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 3, 'user_id' => 3, 'branch_id' => 1, 'full_name' => 'Quản lý Quận 1', 'position' => 'MANAGER', 'salary' => 18500000, 'phone' => '0909000211', 'hire_date' => '2025-08-10', 'birthday' => '1992-03-12', 'avatar' => null, 'experience' => '4 năm', 'notes' => 'Quản lý chi nhánh Quận 1.', 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 4, 'user_id' => 4, 'branch_id' => 3, 'full_name' => 'Quản lý Quận 7', 'position' => 'MANAGER', 'salary' => 18500000, 'phone' => '0909000212', 'hire_date' => '2025-09-01', 'birthday' => '1993-04-18', 'avatar' => null, 'experience' => '3 năm', 'notes' => 'Quản lý chi nhánh Quận 7.', 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 5, 'user_id' => 5, 'branch_id' => 2, 'full_name' => 'Quản lý Thủ Đức', 'position' => 'MANAGER', 'salary' => 18500000, 'phone' => '0909000213', 'hire_date' => '2025-09-10', 'birthday' => '1994-05-22', 'avatar' => null, 'experience' => '3 năm', 'notes' => 'Quản lý chi nhánh Thủ Đức.', 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 6, 'user_id' => 6, 'branch_id' => 4, 'full_name' => 'Groomer Gò Vấp', 'position' => 'GROOMER', 'salary' => 14000000, 'phone' => '0909000220', 'hire_date' => '2025-10-01', 'birthday' => '1996-06-10', 'avatar' => null, 'experience' => '2 năm', 'notes' => 'Nhân viên chăm sóc Gò Vấp.', 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 7, 'user_id' => 7, 'branch_id' => 1, 'full_name' => 'Groomer Quận 1', 'position' => 'GROOMER', 'salary' => 13800000, 'phone' => '0909000221', 'hire_date' => '2025-10-10', 'birthday' => '1997-07-11', 'avatar' => null, 'experience' => '2 năm', 'notes' => 'Nhân viên chăm sóc Quận 1.', 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 8, 'user_id' => 8, 'branch_id' => 3, 'full_name' => 'Groomer Quận 7', 'position' => 'GROOMER', 'salary' => 13800000, 'phone' => '0909000222', 'hire_date' => '2025-11-01', 'birthday' => '1998-08-12', 'avatar' => null, 'experience' => '1 năm', 'notes' => 'Nhân viên chăm sóc Quận 7.', 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 9, 'user_id' => 9, 'branch_id' => 2, 'full_name' => 'Groomer Thủ Đức', 'position' => 'GROOMER', 'salary' => 13800000, 'phone' => '0909000223', 'hire_date' => '2025-11-10', 'birthday' => '1999-09-13', 'avatar' => null, 'experience' => '1 năm', 'notes' => 'Nhân viên chăm sóc Thủ Đức.', 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 10, 'user_id' => 10, 'branch_id' => 4, 'full_name' => 'Nhân viên kho Demo', 'position' => 'OTHER', 'salary' => 13000000, 'phone' => '0909000229', 'hire_date' => '2025-12-01', 'birthday' => '1995-10-14', 'avatar' => null, 'experience' => 'Quản lý tồn kho', 'notes' => 'Theo dõi vật tư demo.', 'created_at' => $now, 'updated_at' => $now],
        ]);

         /*
         * CUSTOMER + PET DEMO DATA
         * - 4 chi nhánh hiện có
         * - 80 khách / chi nhánh = 320 khách
         * - mỗi khách có 1-2 pet
         * - khoảng 448 pet
         * - dữ liệu tạo trong giai đoạn 12/2025 -> 05/2026
         *
         * ID bắt đầu từ 1001 để không đụng dữ liệu mẫu cũ hoặc user nhân viên.
         */

        $customerUsers = [];
        $customers = [];
        $pets = [];

        $customerUserId = 1001;
        $customerId = 1001;
        $petId = 1001;

        $password = \Illuminate\Support\Facades\Hash::make('password123');

        $branches = [
            1 => [
                'name' => 'Pet Hotel Quận 1',
                'area' => 'Quận 1',
                'streets' => ['Nguyễn Huệ', 'Lê Lợi', 'Đồng Khởi', 'Hai Bà Trưng', 'Pasteur'],
            ],
            2 => [
                'name' => 'Pet Hotel Thủ Đức',
                'area' => 'TP. Thủ Đức',
                'streets' => ['Võ Văn Ngân', 'Kha Vạn Cân', 'Hoàng Diệu 2', 'Đặng Văn Bi', 'Linh Trung'],
            ],
            3 => [
                'name' => 'Pet Hotel Quận 7',
                'area' => 'Quận 7',
                'streets' => ['Nguyễn Thị Thập', 'Nguyễn Văn Linh', 'Lâm Văn Bền', 'Huỳnh Tấn Phát', 'Tân Mỹ'],
            ],
            4 => [
                'name' => 'Pet Hotel Gò Vấp',
                'area' => 'Gò Vấp',
                'streets' => ['Phan Văn Trị', 'Quang Trung', 'Lê Đức Thọ', 'Nguyễn Oanh', 'Thống Nhất'],
            ],
        ];

        $lastNames = [
            'Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Võ', 'Đặng',
            'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý', 'Mai', 'Tạ',
        ];

        $middleNames = [
            'Minh', 'Gia', 'Thanh', 'Khánh', 'Ngọc', 'Anh', 'Bảo', 'Quốc',
            'Thảo', 'Đức', 'Hoài', 'Tuấn', 'Nhật', 'Phương', 'Thiên', 'Hải',
        ];

        $firstNames = [
            'An', 'Bình', 'Chi', 'Duy', 'Hân', 'Khoa', 'Linh', 'Nam',
            'Nhi', 'Phúc', 'Quân', 'Trang', 'Vy', 'Huy', 'Ngân', 'Tú',
            'My', 'Long', 'Tâm', 'Thư',
        ];

        $petNames = [
            'Milo', 'Coco', 'Miu', 'Bắp', 'Gấu', 'Đậu', 'Nâu', 'Sữa',
            'Lucky', 'Bella', 'Max', 'Nori', 'Rocky', 'Luna', 'Bông', 'Simba',
            'Mochi', 'Ken', 'Bim', 'Mực', 'Tôm', 'Bơ', 'Kem', 'Mun',
        ];

        $dogBreeds = [
            'Poodle', 'Corgi', 'Pomeranian', 'Beagle', 'Golden Retriever',
            'Shiba', 'Husky', 'Chihuahua', 'Pug', 'Samoyed',
        ];

        $catBreeds = [
            'Mèo ta', 'British Shorthair', 'Scottish Fold', 'Siamese',
            'Maine Coon', 'Munchkin', 'Ragdoll', 'Ba Tư',
        ];

        $birdBreeds = ['Yến phụng', 'Vẹt nhỏ', 'Chim sẻ cảnh'];
        $rabbitBreeds = ['Thỏ Hà Lan', 'Thỏ Mini Lop', 'Thỏ trắng'];
        $otherBreeds = ['Hamster', 'Nhím kiểng', 'Sóc cảnh'];

        $monthCustomerCounts = [
            '2025-12' => 40,
            '2026-01' => 45,
            '2026-02' => 65,
            '2026-03' => 50,
            '2026-04' => 55,
            '2026-05' => 65,
        ];

        $createdDates = [];
        foreach ($monthCustomerCounts as $month => $count) {
            $monthStart = Carbon::parse($month . '-01 08:00:00');
            $daysInMonth = $monthStart->daysInMonth;

            for ($i = 0; $i < $count; $i++) {
                $createdDates[] = $monthStart->copy()
                    ->addDays($i % $daysInMonth)
                    ->setTime(8 + ($i % 10), ($i * 7) % 60, 0);
            }
        }

        $globalCustomerIndex = 0;
        $globalPetIndex = 0;

        foreach ($branches as $branchId => $branchInfo) {
            for ($i = 1; $i <= 80; $i++) {
                $createdAt = $createdDates[$globalCustomerIndex] ?? Carbon::parse('2026-05-25 08:00:00');

                $lastName = $lastNames[$globalCustomerIndex % count($lastNames)];
                $middleName = $middleNames[intdiv($globalCustomerIndex, 3) % count($middleNames)];
                $firstName = $firstNames[intdiv($globalCustomerIndex, 5) % count($firstNames)];
                $fullName = $lastName . ' ' . $middleName . ' ' . $firstName;

                $email = 'customer' . str_pad((string) $customerId, 4, '0', STR_PAD_LEFT) . '@pethotel.test';
                $phone = '0988' . str_pad((string) $customerId, 6, '0', STR_PAD_LEFT);

                $street = $branchInfo['streets'][$i % count($branchInfo['streets'])];
                $address = (10 + $i) . ' ' . $street . ', ' . $branchInfo['area'] . ', TP.HCM';

                $customerUsers[] = [
                    'id' => $customerUserId,
                    'name' => $fullName,
                    'email' => $email,
                    'email_verified_at' => $createdAt,
                    'password' => $password,
                    'role' => 'CUSTOMER',
                    'is_active' => 1,
                    'last_login_at' => null,
                    'remember_token' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                $customers[] = [
                    'customer_id' => $customerId,
                    'user_id' => $customerUserId,
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'address' => $address,
                    'birthday' => Carbon::parse('1985-01-01')
                        ->addDays(($globalCustomerIndex * 67) % 7000)
                        ->toDateString(),
                    'avatar' => null,
                    'notes' => 'Khách demo thuộc nhóm dữ liệu của ' . $branchInfo['name'] . '.',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                /*
                 * 60% khách có 1 pet, 40% khách có 2 pet.
                 */
                $petCount = ($globalCustomerIndex % 5 < 3) ? 1 : 2;

                for ($p = 1; $p <= $petCount; $p++) {
                    /*
                     * Phân bố loài:
                     * - DOG khoảng 50%
                     * - CAT khoảng 40%
                     * - BIRD/RABBIT/OTHER khoảng 10%
                     */
                    $speciesRoll = $globalPetIndex % 100;

                    if ($speciesRoll < 50) {
                        $species = 'DOG';
                        $breed = $dogBreeds[$globalPetIndex % count($dogBreeds)];
                        $weight = round(3 + (($globalPetIndex * 1.6) % 30), 2);
                    } elseif ($speciesRoll < 90) {
                        $species = 'CAT';
                        $breed = $catBreeds[$globalPetIndex % count($catBreeds)];
                        $weight = round(2 + (($globalPetIndex * 0.5) % 8), 2);
                    } elseif ($speciesRoll < 94) {
                        $species = 'BIRD';
                        $breed = $birdBreeds[$globalPetIndex % count($birdBreeds)];
                        $weight = round(0.2 + (($globalPetIndex % 5) * 0.08), 2);
                    } elseif ($speciesRoll < 98) {
                        $species = 'RABBIT';
                        $breed = $rabbitBreeds[$globalPetIndex % count($rabbitBreeds)];
                        $weight = round(1 + (($globalPetIndex % 8) * 0.25), 2);
                    } else {
                        $species = 'OTHER';
                        $breed = $otherBreeds[$globalPetIndex % count($otherBreeds)];
                        $weight = round(0.5 + (($globalPetIndex % 10) * 0.15), 2);
                    }

                    $petName = $petNames[$globalPetIndex % count($petNames)];
                    if ($p === 2) {
                        $petName .= ' Nhỏ';
                    }

                    $pets[] = [
                        'pet_id' => $petId,
                        'customer_id' => $customerId,
                        'pet_name' => $petName,
                        'species' => $species,
                        'breed' => $breed,
                        'sex' => ['MALE', 'FEMALE', 'UNKNOWN'][$globalPetIndex % 3],
                        'age' => 1 + ($globalPetIndex % 10),
                        'weight_kg' => $weight,
                        'pet_image' => null,
                        'special_notes' => $species === 'DOG' || $species === 'CAT'
                            ? 'Pet thường dùng dịch vụ lưu trú, tắm và chăm sóc.'
                            : 'Pet chủ yếu dùng dịch vụ chăm sóc cơ bản.',
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];

                    $petId++;
                    $globalPetIndex++;
                }

                $customerUserId++;
                $customerId++;
                $globalCustomerIndex++;
            }
        }

        foreach (array_chunk($customerUsers, 100) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        foreach (array_chunk($customers, 100) as $chunk) {
            DB::table('customer')->insert($chunk);
        }

        foreach (array_chunk($pets, 100) as $chunk) {
            DB::table('pet')->insert($chunk);
        }
    }
}