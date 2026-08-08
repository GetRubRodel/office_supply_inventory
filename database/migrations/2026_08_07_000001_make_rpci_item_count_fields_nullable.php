<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow the physical count fields on RPCI items to be stored as NULL.
 *
 * With the "Retrieve All Items" feature, inventory items are imported from the
 * Supplies module with the physical count fields intentionally left blank so
 * they can be completed manually after the physical count. NULL means "count
 * not yet recorded" — a value of 0 would incorrectly imply a physical count of
 * zero was performed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rpci_items', function (Blueprint $table) {
            $table->integer('on_hand_per_count')->nullable()->default(0)->change();
            $table->integer('shortage_quantity')->nullable()->default(0)->change();
            $table->decimal('shortage_value', 15, 2)->nullable()->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('rpci_items', function (Blueprint $table) {
            $table->integer('on_hand_per_count')->nullable(false)->default(0)->change();
            $table->integer('shortage_quantity')->nullable(false)->default(0)->change();
            $table->decimal('shortage_value', 15, 2)->nullable(false)->default(0)->change();
        });
    }
};
