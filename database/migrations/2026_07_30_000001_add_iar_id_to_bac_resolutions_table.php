<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bac_resolutions', function (Blueprint $table) {
            $table->foreignId('iar_id')
                ->nullable()
                ->after('id')
                ->constrained('inspection_acceptance_reports')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bac_resolutions', function (Blueprint $table) {
            $table->dropForeign(['iar_id']);
            $table->dropColumn('iar_id');
        });
    }
};
