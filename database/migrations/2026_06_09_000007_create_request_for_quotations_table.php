<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_for_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_no')->unique();
            $table->foreignId('purchase_request_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->string('canvassed_by_name')->nullable();
            $table->string('canvassed_by_designation')->nullable();
            $table->string('quoted_by_name')->nullable();
            $table->string('quoted_by_supplier')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('request_for_quotations')->cascadeOnDelete();
            $table->string('stock_property_no')->nullable();
            $table->string('unit')->nullable();
            $table->text('item_description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_items');
        Schema::dropIfExists('request_for_quotations');
    }
};
