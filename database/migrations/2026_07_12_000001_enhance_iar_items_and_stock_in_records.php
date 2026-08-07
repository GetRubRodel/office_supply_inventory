<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns to iar_items
        Schema::table('iar_items', function (Blueprint $table) {
            $table->foreignId('supply_id')
                ->nullable()
                ->after('iar_id')
                ->constrained('supplies')
                ->nullOnDelete();

            $table->integer('quantity_accepted')
                ->default(0)
                ->after('quantity');

            $table->integer('quantity_rejected')
                ->default(0)
                ->after('quantity_accepted');

            $table->decimal('unit_cost', 12, 2)
                ->nullable()
                ->after('quantity_rejected');

            $table->foreignId('category_id')
                ->nullable()
                ->after('unit_cost')
                ->constrained('categories')
                ->nullOnDelete();
        });

        // Add columns to stock_in_records
        Schema::table('stock_in_records', function (Blueprint $table) {
            $table->foreignId('iar_id')
                ->nullable()
                ->after('id')
                ->constrained('inspection_acceptance_reports')
                ->nullOnDelete();

            $table->decimal('total_cost', 14, 2)
                ->nullable()
                ->after('unit_cost');
        });

        // Add status columns to inspection_acceptance_reports
        Schema::table('inspection_acceptance_reports', function (Blueprint $table) {
            $table->timestamp('stocked_in_at')
                ->nullable()
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_acceptance_reports', function (Blueprint $table) {
            $table->dropColumn('stocked_in_at');
        });

        Schema::table('stock_in_records', function (Blueprint $table) {
            $table->dropForeign(['iar_id']);
            $table->dropColumn('iar_id');
            $table->dropColumn('total_cost');
        });

        Schema::table('iar_items', function (Blueprint $table) {
            $table->dropForeign(['supply_id']);
            $table->dropForeign(['category_id']);
            $table->dropColumn('supply_id');
            $table->dropColumn('quantity_accepted');
            $table->dropColumn('quantity_rejected');
            $table->dropColumn('unit_cost');
            $table->dropColumn('category_id');
        });
    }
};
