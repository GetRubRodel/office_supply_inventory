<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");
        DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('requested', 'approved', 'issued', 'pending_receipt', 'received', 'cancelled') NOT NULL DEFAULT 'requested'");
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");
        DB::statement("UPDATE requisitions SET status = 'issued' WHERE status = 'pending_receipt'");
        DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('requested', 'approved', 'issued', 'received', 'cancelled') NOT NULL DEFAULT 'requested'");
    }
};
