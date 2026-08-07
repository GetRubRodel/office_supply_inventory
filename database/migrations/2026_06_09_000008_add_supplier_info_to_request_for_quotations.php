<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_for_quotations', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('date');
            $table->string('address')->nullable()->after('company_name');
            $table->string('contact_number')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('request_for_quotations', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'address', 'contact_number']);
        });
    }
};
