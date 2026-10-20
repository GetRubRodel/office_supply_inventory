<?php

use App\Support\BacResolutionText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the free-text BAC Resolution body with the fixed template that
 * carries the five blanks:
 *
 *   Blank 1 — Division        (selected by the authorized user)
 *   Blank 2 — Division        (read-only, retrieved from Blank 1)
 *   Blank 3 — Budget Amount   (entered by the authorized user)
 *   Blank 4 — Division        (read-only, retrieved from Blank 1)
 *   Blank 5 — Budget Amount   (read-only, retrieved from Blank 3)
 *
 *  - `division` and `budget_amount` persist only those two user-entered
 *    values. The remaining three blanks are always derived from them, so no
 *    Division or Budget Amount is ever hardcoded into the document.
 *  - Both columns are added only when missing, so the migration is safe to
 *    re-run against a database that already carries them.
 *  - Existing rows are re-written with the tokenised template and the former
 *    "FURTHER RESOLVED" clause is cleared everywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bac_resolutions', function (Blueprint $table) {
            if (! Schema::hasColumn('bac_resolutions', 'division')) {
                $table->string('division')->nullable()->after('resolution_no');
            }

            if (! Schema::hasColumn('bac_resolutions', 'budget_amount')) {
                $table->string('budget_amount')->nullable()->after('division');
            }
        });

        // One-time data migration for records created under the old free-text
        // template: re-write them with the tokenised template and clear the
        // obsolete "FURTHER RESOLVED" clause. The model's save path no longer
        // touches `further_resolved`, so this is the only place legacy values
        // are cleared. No rows are created or deleted here.
        DB::table('bac_resolutions')->update([
            'preamble' => BacResolutionText::preambleTemplate(),
            'operative_part' => BacResolutionText::operativeTemplate(),
            'closing' => BacResolutionText::closingTemplate(),
            'further_resolved' => null,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('bac_resolutions', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['division', 'budget_amount'],
                fn (string $column): bool => Schema::hasColumn('bac_resolutions', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
