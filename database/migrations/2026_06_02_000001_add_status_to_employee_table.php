<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE employee ADD status NUMBER(1) DEFAULT 1 NOT NULL');
        DB::statement('UPDATE employee SET status = 1');
        DB::statement('ALTER TABLE employee ADD CONSTRAINT ck_employee_status CHECK (status IN (0, 1))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE employee DROP CONSTRAINT ck_employee_status');
        DB::statement('ALTER TABLE employee DROP COLUMN status');
    }
};
