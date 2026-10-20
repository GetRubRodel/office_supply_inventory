<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isMySql = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);

        if ($isMySql) {
            // Temporarily disable strict mode to allow ENUM transition
            DB::statement("SET SESSION sql_mode = ''");

            // Step 1: Expand ENUM to include 'requested' alongside 'pending'
            DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('pending', 'requested', 'approved', 'issued', 'received', 'cancelled') NOT NULL DEFAULT 'requested'");

            // Step 2: Convert existing 'pending' records to 'requested'
            DB::statement("UPDATE requisitions SET status = 'requested' WHERE status = 'pending'");

            // Step 3: Remove 'pending' from the ENUM
            DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('requested', 'approved', 'issued', 'received', 'cancelled') NOT NULL DEFAULT 'requested'");
        } else {
            // SQLite (test runner) / other drivers: rebuild the column with the
            // final allowed values using the driver-agnostic schema builder.
            DB::table('requisitions')->where('status', 'pending')->update(['status' => 'requested']);

            Schema::table('requisitions', function (Blueprint $table) {
                $table->enum('status', ['requested', 'approved', 'issued', 'received', 'cancelled'])
                    ->default('requested')
                    ->change();
            });
        }
    }

    public function down(): void
    {
        $isMySql = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);

        if ($isMySql) {
            DB::statement("SET SESSION sql_mode = ''");

            // Step 1: Add 'pending' back to the ENUM
            DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('pending', 'requested', 'approved', 'issued', 'received', 'cancelled') NOT NULL DEFAULT 'pending'");

            // Step 2: Convert 'requested' records back to 'pending'
            DB::statement("UPDATE requisitions SET status = 'pending' WHERE status = 'requested'");

            // Step 3: Remove 'requested' from the ENUM
            DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('pending', 'approved', 'issued', 'received', 'cancelled') NOT NULL DEFAULT 'pending'");
        } else {
            // Widen the constraint first so existing rows stay valid during the
            // SQLite table rebuild, then migrate the data back.
            Schema::table('requisitions', function (Blueprint $table) {
                $table->enum('status', ['pending', 'requested', 'approved', 'issued', 'received', 'cancelled'])
                    ->default('pending')
                    ->change();
            });

            DB::table('requisitions')->where('status', 'requested')->update(['status' => 'pending']);
        }
    }
};
