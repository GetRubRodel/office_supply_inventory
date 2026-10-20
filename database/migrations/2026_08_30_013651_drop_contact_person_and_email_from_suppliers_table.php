<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the unused `contact_person` and `email` columns from `suppliers`.
     *
     * These columns are not part of the Supplier model's fillable fields, are
     * not used in any Filament resource form/table, and are not referenced by
     * any service, controller, view or report. They were legacy columns from
     * the original schema and are safe to remove.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $columns = Schema::getColumnListing('suppliers');

            if (in_array('contact_person', $columns, true)) {
                $table->dropColumn('contact_person');
            }

            if (in_array('email', $columns, true)) {
                $table->dropColumn('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $columns = Schema::getColumnListing('suppliers');

            if (! in_array('contact_person', $columns, true)) {
                $table->string('contact_person')->nullable();
            }

            if (! in_array('email', $columns, true)) {
                $table->string('email')->nullable();
            }
        });
    }
};
