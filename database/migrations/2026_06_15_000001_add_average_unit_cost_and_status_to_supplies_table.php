<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->decimal('average_unit_cost', 12, 2)->nullable()->after('unit_price')
                ->comment('Moving Average Cost calculated from stock receipts');
            $table->string('status', 20)->default('active')->after('average_unit_cost')
                ->comment('Active, Inactive, Discontinued');
        });
    }

    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn(['average_unit_cost', 'status']);
        });
    }
};
