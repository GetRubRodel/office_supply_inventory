<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_acceptance_reports', function (Blueprint $table) {
            $table->id();
            $table->string('iar_no')->unique();
            $table->foreignId('po_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('supplier_name')->nullable();
            $table->string('po_no')->nullable();
            $table->date('date');
            $table->string('requisitioning_office_dept')->nullable();
            // Inspector
            $table->string('inspector_name')->nullable()->default('ANA FE B. GALANTO');
            $table->string('inspector_designation')->nullable()->default('Admin. Assistant II');
            $table->date('inspection_date')->nullable();
            $table->boolean('inspection_complete')->default(true);
            $table->boolean('inspection_partial')->default(false);
            // Acceptor
            $table->string('acceptor_name')->nullable()->default('RHODELIA J. MANDOLADO');
            $table->string('acceptor_designation')->nullable()->default('Admin. Officer IV');
            $table->date('acceptance_date')->nullable();
            $table->boolean('acceptance_complete')->default(true);
            $table->boolean('acceptance_partial')->default(false);
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('iar_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iar_id')->constrained('inspection_acceptance_reports')->cascadeOnDelete();
            $table->string('stock_no')->nullable();
            $table->string('unit')->nullable();
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iar_items');
        Schema::dropIfExists('inspection_acceptance_reports');
    }
};
