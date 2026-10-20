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
        if (! Schema::hasColumn('bac_resolutions', 'date')) {
            return;
        }

        if (Schema::getColumnType('bac_resolutions', 'date') !== 'date') {
            Schema::table('bac_resolutions', function (Blueprint $table) {
                $table->date('date')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('bac_resolutions', 'date')) {
            return;
        }

        if (Schema::getColumnType('bac_resolutions', 'date') === 'date') {
            Schema::table('bac_resolutions', function (Blueprint $table) {
                $table->dateTime('date')->change();
            });
        }
    }
};
