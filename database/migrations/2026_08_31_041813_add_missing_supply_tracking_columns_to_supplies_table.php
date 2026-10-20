<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            // These columns were declared by earlier migrations that were
            // recorded as "Ran" but never actually applied to the live
            // database (the DB was restored from an outdated SQL dump).
            // Supply::receiveStock() / issueStock() write to them, so their
            // absence caused "unknown column" errors during Stock In.
            if (! Schema::hasColumn('supplies', 'reference_number')) {
                $table->string('reference_number')->nullable()->after('unit_price');
            }

            if (! Schema::hasColumn('supplies', 'last_received_date')) {
                $table->date('last_received_date')->nullable()->after('reference_number');
            }

            if (! Schema::hasColumn('supplies', 'latest_reference_number')) {
                $table->string('latest_reference_number')->nullable()->after('last_received_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn(['reference_number', 'last_received_date', 'latest_reference_number']);
        });
    }
};
