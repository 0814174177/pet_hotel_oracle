<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthSupportSeeder extends Seeder
{
    public function run(): void
    {
        if (Schema::hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')->insert([
                [
                    'email' => 'customer1001@pethotel.test',
                    'token' => hash('sha256', 'demo-reset-token'),
                    'created_at' => now(),
                ],
            ]);
        }

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->insert([
                [
                    'id' => Str::random(40),
                    'user_id' => 1001,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Seeder Demo Session',
                    'payload' => base64_encode(serialize(['login_web_' . sha1('demo') => 1001])),
                    'last_activity' => time(),
                ],
            ]);
        }
    }
}
