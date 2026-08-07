<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Temporarily disable strict mode to allow ENUM transition
        DB::statement("SET SESSION sql_mode = ''");

        // Step 1: Expand ENUM to include 'requested' alongside 'pending'
        DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('pending', 'requested', 'approved', 'issued', 'received', 'cancelled') NOT NULL DEFAULT 'requested'");

        // Step 2: Convert existing 'pending' records to 'requested'
        DB::statement("UPDATE requisitions SET status = 'requested' WHERE status = 'pending'");

        // Step 3: Remove 'pending' from the ENUM
        DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('requested', 'approved', 'issued', 'received', 'cancelled') NOT NULL DEFAULT 'requested'");
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        // Step 1: Add 'pending' back to the ENUM
        DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('pending', 'requested', 'approved', 'issued', 'received', 'cancelled') NOT NULL DEFAULT 'pending'");

        // Step 2: Convert 'requested' records back to 'pending'
        DB::statement("UPDATE requisitions SET status = 'pending' WHERE status = 'requested'");

        // Step 3: Remove 'requested' from the ENUM
        DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('pending', 'approved', 'issued', 'received', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
