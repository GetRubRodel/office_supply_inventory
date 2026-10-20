<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The BAC Resolution's approval details are sourced live from the
 * originating ABC via the abc() relationship (see
 * BacResolution::getApprovedByNameAttribute()). The legacy approved_by_*
 * columns are shadowed by those accessors, so the hardcoded defaults are
 * no longer used and are removed to prevent stale values from appearing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bac_resolutions', function (Blueprint $table) {
            $table->string('approved_by_name')->nullable()->change();
            $table->string('approved_by_designation')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bac_resolutions', function (Blueprint $table) {
            $table->string('approved_by_name')->nullable()->default('ATTY. KEYSIE M. GOMEZ')->change();
            $table->string('approved_by_designation')->nullable()->default('Head of the Procuring Entity')->change();
        });
    }
};