<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_no')->unique();
            $table->foreignId('abc_id')->nullable()->constrained('abstracts_of_canvass')->nullOnDelete();
            $table->string('supplier_name')->nullable();
            $table->string('address')->nullable();
            $table->string('tin')->nullable();
            $table->string('mode_of_procurement')->nullable();
            $table->date('date');
            $table->string('place_of_delivery')->nullable();
            $table->string('delivery_term')->nullable();
            $table->date('date_of_delivery')->nullable();
            $table->string('payment_term')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('amount_in_words')->nullable();
            // Conforme (Supplier)
            $table->string('conforme_name')->nullable();
            $table->date('conforme_date')->nullable();
            // Authorized Official
            $table->string('authorized_official_name')->nullable()->default('ATTY. KEYSIE M. GOMEZ');
            $table->string('authorized_official_designation')->nullable()->default('Authorized Official');
            // Funds Available
            $table->string('funds_available_by')->nullable()->default('ANA FE B. GALANTO');
            $table->string('funds_available_designation')->nullable()->default('Admin Asst. II/Budget Officer');
            $table->string('alobs_no')->nullable();
            $table->decimal('alobs_amount', 14, 2)->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('po_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->string('stock_no')->nullable();
            $table->string('unit')->nullable();
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_items');
        Schema::dropIfExists('purchase_orders');
    }
};
