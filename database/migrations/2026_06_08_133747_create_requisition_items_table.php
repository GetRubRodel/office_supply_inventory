<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained()->cascadeOnDelete();
            $table->string('stock_no')->nullable();
            $table->foreignId('supply_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit')->nullable();
            $table->string('description')->nullable();
            $table->integer('quantity_requested')->default(0);
            $table->boolean('stock_available')->nullable();
            $table->integer('quantity_issued')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_items');
    }
};
