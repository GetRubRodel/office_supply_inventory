<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_acceptance_reports', function (Blueprint $table) {
            $table->foreignId('stocked_in_by_user_id')->nullable()->after('stocked_in_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('supplies', function (Blueprint $table) {
            $table->date('last_received_date')->nullable()->after('reference_number');
            $table->string('latest_reference_number')->nullable()->after('last_received_date');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_acceptance_reports', function (Blueprint $table) {
            $table->dropForeign(['stocked_in_by_user_id']);
            $table->dropColumn('stocked_in_by_user_id');
        });

        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn(['last_received_date', 'latest_reference_number']);
        });
    }
};
