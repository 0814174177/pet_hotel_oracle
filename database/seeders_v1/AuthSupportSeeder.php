<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthSupportSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * password_reset_tokens và sessions là bảng hỗ trợ auth/session của Laravel.
         * Thực tế không bắt buộc seed, nhưng thêm mẫu để đủ bảng.
         */
        DB::table('password_reset_tokens')->insert([
            [
                'email' => 'customer1@pethotel.test',
                'token' => hash('sha256', 'demo-reset-token'),
                'created_at' => now(),
            ],
        ]);

        DB::table('sessions')->insert([
            [
                'id' => Str::random(40),
                'user_id' => 5,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder Demo Session',
                'payload' => base64_encode(serialize(['login_web_' . sha1('demo') => 5])),
                'last_activity' => time(),
            ],
        ]);
    }
}
