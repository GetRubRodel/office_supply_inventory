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
            DB::statement("SET SESSION sql_mode = ''");
            DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('requested', 'approved', 'issued', 'pending_receipt', 'received', 'cancelled') NOT NULL DEFAULT 'requested'");
        } else {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->enum('status', ['requested', 'approved', 'issued', 'pending_receipt', 'received', 'cancelled'])
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
            DB::statement("UPDATE requisitions SET status = 'issued' WHERE status = 'pending_receipt'");
            DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('requested', 'approved', 'issued', 'received', 'cancelled') NOT NULL DEFAULT 'requested'");
        } else {
            // Move data out of 'pending_receipt' before narrowing the constraint
            // so the SQLite table rebuild never hits an invalid value.
            DB::table('requisitions')->where('status', 'pending_receipt')->update(['status' => 'issued']);

            Schema::table('requisitions', function (Blueprint $table) {
                $table->enum('status', ['requested', 'approved', 'issued', 'received', 'cancelled'])
                    ->default('requested')
                    ->change();
            });
        }
    }
};
