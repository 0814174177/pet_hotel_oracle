<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LaravelSystemSeeder extends Seeder
{
    public function run(): void
    {
        $now = time();

        /*
         * Các bảng này là bảng hệ thống Laravel.
         * Thực tế không bắt buộc seed, nhưng thêm dữ liệu mẫu để bộ seed bao phủ đầy đủ migration_v1.
         */
        DB::table('cache')->insert([
            [
                'key' => 'demo_cache_key',
                'value' => serialize('Dữ liệu cache mẫu cho môi trường demo'),
                'expiration' => $now + 3600,
            ],
        ]);

        DB::table('cache_locks')->insert([
            [
                'key' => 'demo_cache_lock',
                'owner' => 'seeder',
                'expiration' => $now + 300,
            ],
        ]);

        DB::table('jobs')->insert([
            [
                'id' => 1,
                'queue' => 'default',
                'payload' => json_encode([
                    'uuid' => 'demo-job-uuid',
                    'displayName' => 'DemoSeederJob',
                    'job' => 'Illuminate\Queue\CallQueuedHandler@call',
                    'maxTries' => null,
                    'maxExceptions' => null,
                    'failOnTimeout' => false,
                    'backoff' => null,
                    'timeout' => null,
                    'retryUntil' => null,
                    'data' => [],
                ], JSON_UNESCAPED_UNICODE),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => $now,
                'created_at' => $now,
            ],
        ]);

        DB::table('job_batches')->insert([
            [
                'id' => 'demo-batch-id',
                'name' => 'Lô Demo',
                'total_jobs' => 1,
                'pending_jobs' => 1,
                'failed_jobs' => 0,
                'failed_job_ids' => json_encode([]),
                'options' => null,
                'cancelled_at' => null,
                'created_at' => $now,
                'finished_at' => null,
            ],
        ]);

        DB::table('failed_jobs')->insert([
            [
                'id' => 1,
                'uuid' => 'demo-failed-job-uuid',
                'connection' => 'database',
                'queue' => 'default',
                'payload' => json_encode(['demo' => true], JSON_UNESCAPED_UNICODE),
                'exception' => 'Bản ghi job thất bại mẫu, chỉ dùng cho dữ liệu seed.',
                'failed_at' => now(),
            ],
        ]);
    }
}