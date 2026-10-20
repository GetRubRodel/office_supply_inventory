<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iar_items', function (Blueprint $table) {
            $table->dropColumn(['quantity_accepted', 'quantity_rejected']);
        });
    }

    public function down(): void
    {
        Schema::table('iar_items', function (Blueprint $table) {
            $table->integer('quantity_accepted')
                ->default(0)
                ->after('quantity');

            $table->integer('quantity_rejected')
                ->default(0)
                ->after('quantity_accepted');
        });
    }
};