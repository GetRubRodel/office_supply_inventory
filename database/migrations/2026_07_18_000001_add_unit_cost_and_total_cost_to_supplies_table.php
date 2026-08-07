<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->default(0)->after('average_unit_cost')
                ->comment('Moving Average Cost (MAC) — system-maintained');
            $table->decimal('total_cost', 14, 2)->default(0)->after('unit_cost')
                ->comment('Total inventory value = current_stock × unit_cost');
        });

        // Backfill: set unit_cost = average_unit_cost, total_cost = current_stock × average_unit_cost
        DB::statement('UPDATE supplies SET unit_cost = COALESCE(average_unit_cost, 0), total_cost = current_stock * COALESCE(average_unit_cost, 0)');
    }

    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'total_cost']);
        });
    }
};
