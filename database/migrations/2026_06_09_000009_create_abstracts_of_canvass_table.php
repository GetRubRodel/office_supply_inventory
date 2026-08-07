<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abstracts_of_canvass', function (Blueprint $table) {
            $table->id();
            $table->string('abc_no')->unique();
            $table->foreignId('rfq_id')->nullable()->constrained('request_for_quotations')->nullOnDelete();
            $table->date('date_of_advertisement')->nullable();
            $table->date('date_of_opening')->nullable();
            // Supplier columns (3)
            $table->string('supplier1_name')->nullable();
            $table->string('supplier2_name')->nullable();
            $table->string('supplier3_name')->nullable();
            // Committee on Awards
            $table->string('chairman_name')->nullable();
            $table->string('chairman_designation')->nullable();
            $table->string('vice_chairman_name')->nullable();
            $table->string('vice_chairman_designation')->nullable();
            $table->string('member1_name')->nullable();
            $table->string('member1_designation')->nullable();
            $table->string('member2_name')->nullable();
            $table->string('member2_designation')->nullable();
            $table->string('member3_name')->nullable();
            $table->string('member3_designation')->nullable();
            // Approval
            $table->string('approved_by_name')->nullable();
            $table->string('approved_by_designation')->nullable();
            // Recommendation
            $table->text('recommendation')->nullable()->default('National Book Store');
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('abc_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abc_id')->constrained('abstracts_of_canvass')->cascadeOnDelete();
            $table->integer('item_number')->nullable();
            $table->string('unit')->nullable();
            $table->integer('quantity')->default(1);
            $table->text('description')->nullable();
            $table->decimal('supplier1_price', 12, 2)->nullable();
            $table->decimal('supplier2_price', 12, 2)->nullable();
            $table->decimal('supplier3_price', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abc_items');
        Schema::dropIfExists('abstracts_of_canvass');
    }
};
