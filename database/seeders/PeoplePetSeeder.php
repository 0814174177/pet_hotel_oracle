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
            ['employee_id' => 1, 'user_id' => 1, 'branch_id' => 4, 'full_name' => 'Admin Demo', 'position' => 'MANAGER', 'salary' => 22000000, 'phone' => '0909000200', 'hire_date' => '2025-05-25', 'birthday' => '1990-01-15', 'avatar' => null, 'experience' => '6 năm quản trị vận hành', 'notes' => 'Tài khoản quản trị demo, theo dõi toàn bộ hệ thống.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 2, 'user_id' => 2, 'branch_id' => 4, 'full_name' => 'Quản lý Gò Vấp', 'position' => 'MANAGER', 'salary' => 19000000, 'phone' => '0909000210', 'hire_date' => '2025-08-01', 'birthday' => '1991-02-20', 'avatar' => null, 'experience' => '4 năm quản lý chi nhánh', 'notes' => 'Quản lý doanh thu, nhân sự và lịch phòng Gò Vấp.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 3, 'user_id' => 3, 'branch_id' => 1, 'full_name' => 'Quản lý Quận 1', 'position' => 'MANAGER', 'salary' => 18500000, 'phone' => '0909000211', 'hire_date' => '2025-08-10', 'birthday' => '1992-03-12', 'avatar' => null, 'experience' => '4 năm quản lý dịch vụ thú cưng', 'notes' => 'Phụ trách chi nhánh Quận 1, khu vực khách hàng văn phòng.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 4, 'user_id' => 4, 'branch_id' => 3, 'full_name' => 'Quản lý Quận 7', 'position' => 'MANAGER', 'salary' => 18500000, 'phone' => '0909000212', 'hire_date' => '2025-09-01', 'birthday' => '1993-04-18', 'avatar' => null, 'experience' => '3 năm quản lý vận hành', 'notes' => 'Phụ trách chi nhánh Quận 7, khu căn hộ và khách cao cấp.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 5, 'user_id' => 5, 'branch_id' => 2, 'full_name' => 'Quản lý Thủ Đức', 'position' => 'MANAGER', 'salary' => 18500000, 'phone' => '0909000213', 'hire_date' => '2025-09-10', 'birthday' => '1994-05-22', 'avatar' => null, 'experience' => '3 năm quản lý chi nhánh', 'notes' => 'Phụ trách chi nhánh Thủ Đức, nhóm khách trẻ và sinh viên.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 6, 'user_id' => 6, 'branch_id' => 4, 'full_name' => 'Groomer Gò Vấp', 'position' => 'GROOMER', 'salary' => 14000000, 'phone' => '0909000220', 'hire_date' => '2025-10-01', 'birthday' => '1996-06-10', 'avatar' => null, 'experience' => '2 năm tắm, cắt tỉa và chăm sóc pet', 'notes' => 'Nhân viên chăm sóc chính tại Gò Vấp.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 7, 'user_id' => 7, 'branch_id' => 1, 'full_name' => 'Groomer Quận 1', 'position' => 'GROOMER', 'salary' => 13800000, 'phone' => '0909000221', 'hire_date' => '2025-10-10', 'birthday' => '1997-07-11', 'avatar' => null, 'experience' => '2 năm grooming thú cưng nhỏ', 'notes' => 'Nhân viên chăm sóc Quận 1.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 8, 'user_id' => 8, 'branch_id' => 3, 'full_name' => 'Groomer Quận 7', 'position' => 'GROOMER', 'salary' => 13800000, 'phone' => '0909000222', 'hire_date' => '2025-11-01', 'birthday' => '1998-08-12', 'avatar' => null, 'experience' => '1 năm chăm sóc chó/mèo', 'notes' => 'Nhân viên chăm sóc Quận 7.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 9, 'user_id' => 9, 'branch_id' => 2, 'full_name' => 'Groomer Thủ Đức', 'position' => 'GROOMER', 'salary' => 13800000, 'phone' => '0909000223', 'hire_date' => '2025-11-10', 'birthday' => '1999-09-13', 'avatar' => null, 'experience' => '1 năm chăm sóc thú cưng', 'notes' => 'Nhân viên chăm sóc Thủ Đức.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 10, 'user_id' => 10, 'branch_id' => 4, 'full_name' => 'Nhân viên kho Demo', 'position' => 'OTHER', 'salary' => 13000000, 'phone' => '0909000229', 'hire_date' => '2025-12-01', 'birthday' => '1995-10-14', 'avatar' => null, 'experience' => 'Quản lý tồn kho, nhập vật tư, kiểm kê', 'notes' => 'Theo dõi vật tư demo toàn chuỗi.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 11, 'user_id' => 11, 'branch_id' => 1, 'full_name' => 'Lễ tân Quận 1', 'position' => 'RECEPTIONIST', 'salary' => 11500000, 'phone' => '0909000231', 'hire_date' => '2025-09-15', 'birthday' => '1998-11-02', 'avatar' => null, 'experience' => '2 năm tư vấn và nhận đặt lịch', 'notes' => 'Tiếp nhận booking, hỗ trợ khách hàng Quận 1.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 12, 'user_id' => 12, 'branch_id' => 2, 'full_name' => 'Lễ tân Thủ Đức', 'position' => 'RECEPTIONIST', 'salary' => 11200000, 'phone' => '0909000232', 'hire_date' => '2025-09-20', 'birthday' => '1999-12-03', 'avatar' => null, 'experience' => '1 năm chăm sóc khách hàng', 'notes' => 'Tiếp nhận booking, hỗ trợ thanh toán Thủ Đức.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 13, 'user_id' => 13, 'branch_id' => 3, 'full_name' => 'Lễ tân Quận 7', 'position' => 'RECEPTIONIST', 'salary' => 11500000, 'phone' => '0909000233', 'hire_date' => '2025-09-25', 'birthday' => '1997-01-04', 'avatar' => null, 'experience' => '2 năm tư vấn khách hàng', 'notes' => 'Phụ trách lịch đặt phòng và hồ sơ khách Quận 7.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 14, 'user_id' => 14, 'branch_id' => 4, 'full_name' => 'Lễ tân Gò Vấp', 'position' => 'RECEPTIONIST', 'salary' => 11200000, 'phone' => '0909000234', 'hire_date' => '2025-10-05', 'birthday' => '1998-02-05', 'avatar' => null, 'experience' => '1 năm đặt lịch và chăm sóc khách hàng', 'notes' => 'Hỗ trợ check-in/check-out tại Gò Vấp.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 15, 'user_id' => 15, 'branch_id' => 1, 'full_name' => 'Bác sĩ thú y Quận 1', 'position' => 'VET', 'salary' => 21000000, 'phone' => '0909000241', 'hire_date' => '2025-07-01', 'birthday' => '1989-06-18', 'avatar' => null, 'experience' => '7 năm khám sức khỏe thú cưng', 'notes' => 'Kiểm tra sức khỏe trước lưu trú và xử lý ca đặc biệt.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 16, 'user_id' => 16, 'branch_id' => 4, 'full_name' => 'Bác sĩ thú y Gò Vấp', 'position' => 'VET', 'salary' => 20500000, 'phone' => '0909000242', 'hire_date' => '2025-07-15', 'birthday' => '1990-07-19', 'avatar' => null, 'experience' => '6 năm chăm sóc, theo dõi sức khỏe pet', 'notes' => 'Hỗ trợ khám nhanh và tư vấn chăm sóc.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 17, 'user_id' => 17, 'branch_id' => 2, 'full_name' => 'Nhân viên vệ sinh Thủ Đức', 'position' => 'CLEANER', 'salary' => 9500000, 'phone' => '0909000243', 'hire_date' => '2025-11-20', 'birthday' => '2000-08-20', 'avatar' => null, 'experience' => 'Vệ sinh chuồng phòng, khu lưu trú', 'notes' => 'Đảm bảo vệ sinh phòng sau checkout.', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['employee_id' => 18, 'user_id' => 18, 'branch_id' => 3, 'full_name' => 'Groomer cũ Demo', 'position' => 'GROOMER', 'salary' => 12000000, 'phone' => '0909000244', 'hire_date' => '2025-06-01', 'birthday' => '1996-09-21', 'avatar' => null, 'experience' => 'Đã nghỉ việc, giữ lại để demo', 'notes' => 'Nhân viên không hoạt động, không được dùng để phân công booking/order.', 'status' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);

         /*
         * CUSTOMER + PET DEMO DATA
         * - 4 chi nhánh hiện có
         * - 80 khách / chi nhánh = 320 khách
         * - mỗi khách có 1-2 pet
         * - khoảng 448 pet
         * - dữ liệu tạo trong giai đoạn 11/2025 -> 05/2026
         * - ID bắt đầu từ 1001 để tách rõ với tài khoản nhân sự.
         */

        $customerUsers = [];
        $customers = [];
        $pets = [];

        $customerUserId = 1001;
        $customerId = 1001;
        $petId = 1001;

        $password = \Illuminate\Support\Facades\Hash::make('password123');

        $branches = [
            1 => ['name' => 'Pet Hotel Quận 1', 'area' => 'Quận 1', 'streets' => ['Nguyễn Huệ', 'Lê Lợi', 'Đồng Khởi', 'Hai Bà Trưng', 'Pasteur']],
            2 => ['name' => 'Pet Hotel Thủ Đức', 'area' => 'TP. Thủ Đức', 'streets' => ['Võ Văn Ngân', 'Kha Vạn Cân', 'Hoàng Diệu 2', 'Đặng Văn Bi', 'Linh Trung']],
            3 => ['name' => 'Pet Hotel Quận 7', 'area' => 'Quận 7', 'streets' => ['Nguyễn Thị Thập', 'Nguyễn Văn Linh', 'Lâm Văn Bền', 'Huỳnh Tấn Phát', 'Tân Mỹ']],
            4 => ['name' => 'Pet Hotel Gò Vấp', 'area' => 'Gò Vấp', 'streets' => ['Phan Văn Trị', 'Quang Trung', 'Lê Đức Thọ', 'Nguyễn Oanh', 'Thống Nhất']],
        ];

        $lastNames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý', 'Mai', 'Tạ'];
        $middleNames = ['Minh', 'Gia', 'Thanh', 'Khánh', 'Ngọc', 'Anh', 'Bảo', 'Quốc', 'Thảo', 'Đức', 'Hoài', 'Tuấn', 'Nhật', 'Phương', 'Thiên', 'Hải'];
        $firstNames = ['An', 'Bình', 'Chi', 'Duy', 'Hân', 'Khoa', 'Linh', 'Nam', 'Nhi', 'Phúc', 'Quân', 'Trang', 'Vy', 'Huy', 'Ngân', 'Tú', 'My', 'Long', 'Tâm', 'Thư'];
        $petNames = ['Milo', 'Coco', 'Miu', 'Bắp', 'Gấu', 'Đậu', 'Nâu', 'Sữa', 'Lucky', 'Bella', 'Max', 'Nori', 'Rocky', 'Luna', 'Bông', 'Simba', 'Mochi', 'Ken', 'Bim', 'Mực', 'Tôm', 'Bơ', 'Kem', 'Mun'];
        $dogBreeds = ['Poodle', 'Corgi', 'Pomeranian', 'Beagle', 'Golden Retriever', 'Shiba', 'Husky', 'Chihuahua', 'Pug', 'Samoyed'];
        $catBreeds = ['Mèo ta', 'British Shorthair', 'Scottish Fold', 'Siamese', 'Maine Coon', 'Munchkin', 'Ragdoll', 'Ba Tư'];
        $birdBreeds = ['Yến phụng', 'Vẹt nhỏ', 'Chim sẻ cảnh'];
        $rabbitBreeds = ['Thỏ Hà Lan', 'Thỏ Mini Lop', 'Thỏ trắng'];
        $otherBreeds = ['Hamster', 'Nhím kiểng', 'Sóc cảnh'];

        $monthCustomerCounts = [
            '2025-11' => 20,
            '2025-12' => 40,
            '2026-01' => 45,
            '2026-02' => 65,
            '2026-03' => 50,
            '2026-04' => 55,
            '2026-05' => 45,
        ];

        $createdDates = [];
        foreach ($monthCustomerCounts as $month => $count) {
            $monthStart = Carbon::parse($month . '-01 08:00:00');
            $daysInMonth = $monthStart->daysInMonth;
            for ($i = 0; $i < $count; $i++) {
                $createdDates[] = $monthStart->copy()->addDays($i % $daysInMonth)->setTime(8 + ($i % 10), ($i * 7) % 60, 0);
            }
        }

        $globalCustomerIndex = 0;
        $globalPetIndex = 0;

        foreach ($branches as $branchId => $branchInfo) {
            for ($i = 1; $i <= 80; $i++) {
                // Rải ngày tạo khách đều cho từng chi nhánh trong toàn bộ 11/2025 -> 05/2026.
                // Tránh tình trạng booking tháng 12 ở một chi nhánh nhưng customer của chi nhánh đó tới tháng 3-5 mới được tạo.
                $dateIndex = (($i - 1) * count($branches) + ($branchId - 1)) % count($createdDates);
                $createdAt = $createdDates[$dateIndex] ?? Carbon::parse('2026-05-25 08:00:00');
                $lastName = $lastNames[$globalCustomerIndex % count($lastNames)];
                $middleName = $middleNames[intdiv($globalCustomerIndex, 3) % count($middleNames)];
                $firstName = $firstNames[intdiv($globalCustomerIndex, 5) % count($firstNames)];
                $fullName = $lastName . ' ' . $middleName . ' ' . $firstName;
                $email = 'customer' . str_pad((string) $customerId, 4, '0', STR_PAD_LEFT) . '@pethotel.test';
                $phone = '0988' . str_pad((string) $customerId, 6, '0', STR_PAD_LEFT);
                $street = $branchInfo['streets'][$i % count($branchInfo['streets'])];
                $address = (10 + $i) . ' ' . $street . ', ' . $branchInfo['area'] . ', TP.HCM';

                $customerUsers[] = ['id' => $customerUserId, 'name' => $fullName, 'email' => $email, 'email_verified_at' => $createdAt, 'password' => $password, 'role' => 'CUSTOMER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $createdAt, 'updated_at' => $createdAt];
                $customers[] = ['customer_id' => $customerId, 'user_id' => $customerUserId, 'full_name' => $fullName, 'phone' => $phone, 'address' => $address, 'birthday' => Carbon::parse('1985-01-01')->addDays(($globalCustomerIndex * 67) % 7000)->toDateString(), 'avatar' => null, 'notes' => 'Khách demo thuộc nhóm dữ liệu của ' . $branchInfo['name'] . '.', 'created_at' => $createdAt, 'updated_at' => $createdAt];

                $petCount = ($globalCustomerIndex % 5 < 3) ? 1 : 2;
                for ($p = 1; $p <= $petCount; $p++) {
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

                    $pets[] = ['pet_id' => $petId, 'customer_id' => $customerId, 'pet_name' => $petName, 'species' => $species, 'breed' => $breed, 'sex' => ['MALE', 'FEMALE', 'UNKNOWN'][$globalPetIndex % 3], 'age' => 1 + ($globalPetIndex % 10), 'weight_kg' => $weight, 'pet_image' => null, 'special_notes' => $species === 'DOG' || $species === 'CAT' ? 'Pet thường dùng dịch vụ lưu trú, tắm và chăm sóc.' : 'Pet chủ yếu dùng dịch vụ chăm sóc cơ bản.', 'created_at' => $createdAt, 'updated_at' => $createdAt];
                    $petId++;
                    $globalPetIndex++;
                }

                $customerUserId++;
                $customerId++;
                $globalCustomerIndex++;
            }
        }

        foreach (array_chunk($customerUsers, 100) as $chunk) { DB::table('users')->insert($chunk); }
        foreach (array_chunk($customers, 100) as $chunk) { DB::table('customer')->insert($chunk); }
        foreach (array_chunk($pets, 100) as $chunk) { DB::table('pet')->insert($chunk); }
    }
}
