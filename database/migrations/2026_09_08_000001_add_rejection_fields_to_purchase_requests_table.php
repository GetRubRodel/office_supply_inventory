<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
            $table->string('rejected_by_name')->nullable()->after('rejection_reason');
            $table->string('rejected_by_designation')->nullable()->after('rejected_by_name');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by_designation');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn([
                'rejection_reason',
                'rejected_by_name',
                'rejected_by_designation',
                'rejected_at',
            ]);
        });
    }
};
