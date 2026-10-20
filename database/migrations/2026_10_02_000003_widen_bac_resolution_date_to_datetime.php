<?php

use App\Support\BacResolutionText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Puts the date the resolution was "DONE this" into the closing statement.
 *
 *  - `bac_resolutions.date` is widened from DATE to DATETIME so the time part
 *    picked on the new DateTimePicker is actually persisted instead of being
 *    silently truncated.
 *  - The stored `closing` column is re-written with the new tokenised
 *    template so the {@see BacResolutionText::RESOLVED_DATE_TOKEN} blank
 *    exists on records created before this change. Existing rows keep their
 *    date and numbering; nothing is created or deleted.
 *
 * Both steps are guarded so the migration is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bac_resolutions', 'date')) {
            return;
        }

        // Only widen when still a plain DATE; makes re-running a no-op.
        if (Schema::getColumnType('bac_resolutions', 'date') !== 'datetime') {
            Schema::table('bac_resolutions', function (Blueprint $table) {
                $table->dateTime('date')->change();
            });
        }

        // Re-write the closing so the resolved-date blank is present. The
        // model's save path always writes this template too; this only covers
        // records that have not been re-saved since the template changed.
        DB::table('bac_resolutions')->update([
            'closing' => BacResolutionText::closingTemplate(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('bac_resolutions', 'date')) {
            return;
        }

        // Dropping the time part cannot be recovered — the discarded value is
        // the date component of each stored DATETIME.
        if (Schema::getColumnType('bac_resolutions', 'date') === 'datetime') {
            Schema::table('bac_resolutions', function (Blueprint $table) {
                $table->date('date')->change();
            });
        }

        DB::table('bac_resolutions')->update([
            'closing' => 'SO RESOLVED.',
            'updated_at' => now(),
        ]);
    }
};