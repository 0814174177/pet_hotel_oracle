<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CleanupSeeder::class,
            LaravelSystemSeeder::class,
            UserSeeder::class,
            BranchRoomSeeder::class,
            ProductServiceSeeder::class,
            PeoplePetSeeder::class,
            AuthSupportSeeder::class,
            CouponInventorySeeder::class,
            BookingSeeder::class,
            OrderSeeder::class,
            AuditLogSeeder::class,
            OracleSequenceSeeder::class,
        ]);
    }
}
