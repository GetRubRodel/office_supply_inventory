<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rpci_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rpci_id')->constrained('rpcis')->cascadeOnDelete();
            $table->foreignId('supply_id')->nullable()->constrained()->nullOnDelete();

            $table->string('article')->nullable();
            $table->text('description')->nullable();
            $table->string('stock_no')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('unit_value', 15, 2)->default(0);

            // Quantities
            $table->integer('balance_per_card')->default(0);
            $table->integer('on_hand_per_count')->default(0);

            // Computed fields
            $table->integer('shortage_overage_qty')->default(0);
            $table->decimal('shortage_overage_value', 15, 2)->default(0);

            $table->text('remarks')->nullable();
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rpci_items');
    }
};
