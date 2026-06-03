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

        DB::table('customer')->insert([
            ['customer_id' => 1, 'user_id' => 11, 'full_name' => 'Nguyễn Minh Ánh', 'phone' => '0909000301', 'address' => '15 Lê Đức Thọ, Gò Vấp, TP.HCM', 'birthday' => '1998-01-01', 'avatar' => null, 'notes' => 'Khách demo đặt 2 thú cưng nhỏ.', 'created_at' => $now, 'updated_at' => $now],
            ['customer_id' => 2, 'user_id' => 12, 'full_name' => 'Trần Gia Huy', 'phone' => '0909000302', 'address' => '22 Nguyễn Trãi, Quận 1, TP.HCM', 'birthday' => '1995-02-02', 'avatar' => null, 'notes' => 'Khách demo phòng vừa.', 'created_at' => $now, 'updated_at' => $now],
            ['customer_id' => 3, 'user_id' => 13, 'full_name' => 'Lê Hoàng Nam', 'phone' => '0909000303', 'address' => '45 Nguyễn Văn Linh, Quận 7, TP.HCM', 'birthday' => '1993-03-03', 'avatar' => null, 'notes' => 'Khách demo phòng lớn đã hoàn tất.', 'created_at' => $now, 'updated_at' => $now],
            ['customer_id' => 4, 'user_id' => 14, 'full_name' => 'Phạm Thu Trang', 'phone' => '0909000304', 'address' => '88 Phan Xích Long, Phú Nhuận, TP.HCM', 'birthday' => '1996-04-04', 'avatar' => null, 'notes' => 'Khách demo giữ phòng để test hết phòng.', 'created_at' => $now, 'updated_at' => $now],
            ['customer_id' => 5, 'user_id' => 15, 'full_name' => 'Võ Khánh Linh', 'phone' => '0909000305', 'address' => '12 Tôn Đức Thắng, Quận 1, TP.HCM', 'birthday' => '1999-05-05', 'avatar' => null, 'notes' => 'Khách demo chi nhánh Quận 1.', 'created_at' => $now, 'updated_at' => $now],
            ['customer_id' => 6, 'user_id' => 16, 'full_name' => 'Đặng Quốc Bảo', 'phone' => '0909000306', 'address' => '90 Nguyễn Thị Thập, Quận 7, TP.HCM', 'birthday' => '1994-06-06', 'avatar' => null, 'notes' => 'Khách demo chi nhánh Quận 7.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('pet')->insert([
            ['pet_id' => 1, 'customer_id' => 1, 'pet_name' => 'Milo', 'species' => 'DOG', 'breed' => 'Poodle', 'sex' => 'MALE', 'age' => 3, 'weight_kg' => 5.00, 'pet_image' => null, 'special_notes' => 'Chó nhỏ, hiền, dễ chăm sóc.', 'created_at' => $now, 'updated_at' => $now],
            ['pet_id' => 2, 'customer_id' => 1, 'pet_name' => 'Bella', 'species' => 'DOG', 'breed' => 'Corgi', 'sex' => 'FEMALE', 'age' => 2, 'weight_kg' => 8.00, 'pet_image' => null, 'special_notes' => 'Cần ở cùng Milo trong cùng lần booking.', 'created_at' => $now, 'updated_at' => $now],
            ['pet_id' => 3, 'customer_id' => 2, 'pet_name' => 'Lucky', 'species' => 'DOG', 'breed' => 'Beagle', 'sex' => 'MALE', 'age' => 4, 'weight_kg' => 15.00, 'pet_image' => null, 'special_notes' => 'Chó cỡ vừa, thích dịch vụ tắm.', 'created_at' => $now, 'updated_at' => $now],
            ['pet_id' => 4, 'customer_id' => 3, 'pet_name' => 'Max', 'species' => 'DOG', 'breed' => 'Golden Retriever', 'sex' => 'MALE', 'age' => 5, 'weight_kg' => 30.00, 'pet_image' => null, 'special_notes' => 'Chó lớn, cần phòng rộng.', 'created_at' => $now, 'updated_at' => $now],
            ['pet_id' => 5, 'customer_id' => 4, 'pet_name' => 'Coco', 'species' => 'DOG', 'breed' => 'Shiba', 'sex' => 'FEMALE', 'age' => 3, 'weight_kg' => 18.00, 'pet_image' => null, 'special_notes' => 'Dùng để demo giữ phòng thứ hai.', 'created_at' => $now, 'updated_at' => $now],
            ['pet_id' => 6, 'customer_id' => 5, 'pet_name' => 'Nori', 'species' => 'DOG', 'breed' => 'Pomeranian', 'sex' => 'FEMALE', 'age' => 2, 'weight_kg' => 4.50, 'pet_image' => null, 'special_notes' => 'Chó nhỏ tại chi nhánh Quận 1.', 'created_at' => $now, 'updated_at' => $now],
            ['pet_id' => 7, 'customer_id' => 6, 'pet_name' => 'Rocky', 'species' => 'DOG', 'breed' => 'Husky', 'sex' => 'MALE', 'age' => 4, 'weight_kg' => 28.00, 'pet_image' => null, 'special_notes' => 'Chó lớn tại chi nhánh Quận 7.', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}