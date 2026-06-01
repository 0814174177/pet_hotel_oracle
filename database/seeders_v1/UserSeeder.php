<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::parse('2026-05-25 08:00:00');
        $password = Hash::make('password123');

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Admin Demo', 'email' => 'admin.demo@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'ADMIN', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Manager Go Vap', 'email' => 'manager.govap@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'MANAGER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Manager Quan 1', 'email' => 'manager.q1@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'MANAGER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => 'Manager Quan 7', 'email' => 'manager.q7@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'MANAGER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => 'Manager Thu Duc', 'email' => 'manager.thuduc@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'MANAGER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'name' => 'Groomer Go Vap', 'email' => 'groomer.govap@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'GROOMER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'name' => 'Groomer Quan 1', 'email' => 'groomer.q1@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'GROOMER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'name' => 'Groomer Quan 7', 'email' => 'groomer.q7@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'GROOMER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9, 'name' => 'Groomer Thu Duc', 'email' => 'groomer.thuduc@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'GROOMER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'name' => 'Inventory Staff', 'email' => 'inventory.demo@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'MANAGER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'name' => 'Nguyen Minh Anh', 'email' => 'customer.small@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'CUSTOMER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'name' => 'Tran Gia Huy', 'email' => 'customer.medium@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'CUSTOMER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 13, 'name' => 'Le Hoang Nam', 'email' => 'customer.large@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'CUSTOMER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'name' => 'Pham Thu Trang', 'email' => 'customer.capacity@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'CUSTOMER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 15, 'name' => 'Vo Khanh Linh', 'email' => 'customer.north@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'CUSTOMER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 16, 'name' => 'Dang Quoc Bao', 'email' => 'customer.east@pethotel.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'CUSTOMER', 'is_active' => 1, 'last_login_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
