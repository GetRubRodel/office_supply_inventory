<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enforces the Purchase Order ← BAC Resolution document dependency:
 *
 *  - Links each Purchase Order to the BAC Resolution it was created from.
 *  - Stores a denormalized snapshot of the source resolution number
 *    (auto-populated when the Purchase Order is created).
 *  - Adds a UNIQUE index on purchase_orders.bac_resolution_id so a single
 *    BAC Resolution can only ever be converted into ONE Purchase Order
 *    (duplicate prevention at the database level, regardless of how the
 *    record is inserted).
 *
 * The BAC Resolution is considered "Converted to Purchase Order" when a row
 * in purchase_orders references it — no status column is touched on the BAC
 * Resolution, which keeps the existing BAC Resolution workflow intact.
 *
 * The existing purchase_orders.abc_id relationship is preserved: it is now
 * derived automatically from the source BAC Resolution's own ABC so reports
 * traversing PO → ABC → RFQ → PR keep working unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('bac_resolution_id')
                ->nullable()
                ->after('abc_id')
                ->constrained('bac_resolutions')
                ->nullOnDelete();

            $table->string('resolution_no')->nullable()->after('bac_resolution_id');

            // One BAC Resolution may only be converted into one Purchase Order.
            $table->unique('bac_resolution_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropUnique(['bac_resolution_id']);
            $table->dropConstrainedForeignId('bac_resolution_id');
            $table->dropColumn('resolution_no');
        });
    }
};
