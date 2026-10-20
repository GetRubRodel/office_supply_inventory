<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enforces the BAC Resolution ← Abstract of Bids and Canvass (ABC)
 * document dependency:
 *
 *  - Links each BAC Resolution to the ABC it was created from.
 *  - Stores a denormalized snapshot of the source ABC number (auto-populated
 *    when the BAC Resolution is created).
 *  - Adds a UNIQUE index on bac_resolutions.abc_id so a single ABC can only
 *    ever be converted into ONE BAC Resolution (duplicate prevention at the
 *    database level, regardless of how the record is inserted).
 *
 * The ABC is considered "Converted to BAC Resolution" when a row in
 * bac_resolutions references it — no status column is touched on the ABC,
 * which keeps the existing ABC workflow intact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bac_resolutions', function (Blueprint $table) {
            $table->foreignId('abc_id')
                ->nullable()
                ->after('id')
                ->constrained('abstracts_of_canvass')
                ->nullOnDelete();

            $table->string('abc_no')->nullable()->after('abc_id');

            // One ABC may only be converted into one BAC Resolution.
            $table->unique('abc_id');
        });
    }

    public function down(): void
    {
        Schema::table('bac_resolutions', function (Blueprint $table) {
            $table->dropUnique(['abc_id']);
            $table->dropConstrainedForeignId('abc_id');
            $table->dropColumn('abc_no');
        });
    }
};
