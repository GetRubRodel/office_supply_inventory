<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add editing lock columns to all 6 Purchase Management tables.
     */
    public function up(): void
    {
        $tables = [
            'purchase_requests',
            'request_for_quotations',
            'abstracts_of_canvass',
            'purchase_orders',
            'bac_resolutions',
            'inspection_acceptance_reports',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('editing_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('id');
                $table->timestamp('editing_started_at')
                    ->nullable()
                    ->after('editing_by_user_id');
            });
        }
    }

    /**
     * Reverse the changes.
     */
    public function down(): void
    {
        $tables = [
            'purchase_requests',
            'request_for_quotations',
            'abstracts_of_canvass',
            'purchase_orders',
            'bac_resolutions',
            'inspection_acceptance_reports',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('editing_by_user_id');
                $table->dropColumn('editing_started_at');
            });
        }
    }
};
