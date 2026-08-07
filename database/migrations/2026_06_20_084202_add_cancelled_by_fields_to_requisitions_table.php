<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->string('cancelled_by_name')->nullable()->after('received_by_date');
            $table->string('cancelled_by_designation')->nullable()->after('cancelled_by_name');
            $table->date('cancelled_by_date')->nullable()->after('cancelled_by_designation');
        });
    }

    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropColumn(['cancelled_by_name', 'cancelled_by_designation', 'cancelled_by_date']);
        });
    }
};
