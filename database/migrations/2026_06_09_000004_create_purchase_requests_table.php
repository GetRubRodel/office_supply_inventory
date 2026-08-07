<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_no')->nullable()->unique();
            $table->date('date');
            $table->string('office_division')->nullable();
            $table->string('rc_code')->nullable();
            $table->string('code')->nullable()->comment('Project Code');
            $table->string('name_of_project')->nullable();
            $table->text('purpose')->nullable();
            $table->string('source_of_fund')->nullable();
            $table->decimal('approved_budget', 15, 2)->nullable();
            $table->string('requested_by_name')->nullable();
            $table->string('requested_by_designation')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->string('approved_by_designation')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
