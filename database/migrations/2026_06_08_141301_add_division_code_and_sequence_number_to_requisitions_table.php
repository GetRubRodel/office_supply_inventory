<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->string('division_code', 10)->nullable()->after('division');
            $table->unsignedInteger('sequence_number')->nullable()->after('division_code');

            $table->dropUnique(['ris_no']);
        });
    }

    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropColumn(['division_code', 'sequence_number']);

            $table->unique('ris_no');
        });
    }
};
