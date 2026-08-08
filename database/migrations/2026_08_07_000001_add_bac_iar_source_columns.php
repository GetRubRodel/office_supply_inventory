<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enforces the BAC Resolution ← Inspection and Acceptance Report (IAR)
 * document dependency:
 *
 *  - Stores a denormalized snapshot of the source IAR on the BAC Resolution
 *    (auto-populated when the BAC Resolution is created).
 *  - Adds a UNIQUE index on bac_resolutions.iar_id so a single IAR can only
 *    ever be converted into ONE BAC Resolution (duplicate prevention at the
 *    database level, regardless of how the record is inserted).
 *
 * The IAR is considered "Converted to BAC Resolution" when a row in
 * bac_resolutions references it — no status column is touched on the IAR,
 * which keeps the existing IAR workflow (draft/approved/stocked_in) intact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bac_resolutions', function (Blueprint $table) {
            $table->string('iar_no')->nullable()->after('iar_id');
            $table->string('po_no')->nullable()->after('iar_no');
            $table->string('supplier_name')->nullable()->after('po_no');
            $table->date('inspection_date')->nullable()->after('supplier_name');
            $table->date('acceptance_date')->nullable()->after('inspection_date');
            $table->string('acceptance_details')->nullable()->after('acceptance_date');
            $table->json('items_snapshot')->nullable()->after('acceptance_details');

            // One IAR may only be converted into one BAC Resolution.
            $table->unique('iar_id');
        });
    }

    public function down(): void
    {
        Schema::table('bac_resolutions', function (Blueprint $table) {
            $table->dropUnique(['iar_id']);
            $table->dropColumn([
                'iar_no',
                'po_no',
                'supplier_name',
                'inspection_date',
                'acceptance_date',
                'acceptance_details',
                'items_snapshot',
            ]);
        });
    }
};
