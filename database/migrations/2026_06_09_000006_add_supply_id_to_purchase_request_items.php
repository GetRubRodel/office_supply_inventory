<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->foreignId('supply_id')->nullable()->constrained()->nullOnDelete()->after('purchase_request_id');
            $table->integer('reorder_level')->nullable()->after('stock_property_no')
                ->comment('Reorder level snapshot from supply at time of PR creation');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supply_id');
            $table->dropColumn('reorder_level');
        });
    }
};
