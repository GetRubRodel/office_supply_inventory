<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abstracts_of_canvass', function (Blueprint $table) {
            $table->foreignId('winning_supplier_id')
                ->nullable()
                ->after('recommendation')
                ->constrained('suppliers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('abstracts_of_canvass', function (Blueprint $table) {
            $table->dropForeign(['winning_supplier_id']);
            $table->dropColumn('winning_supplier_id');
        });
    }
};
