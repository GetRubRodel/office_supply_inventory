<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rpcis', function (Blueprint $table) {
            $table->id();
            $table->string('report_no')->unique()->nullable();
            $table->string('inventory_type')->nullable();
            $table->date('report_date');
            $table->string('fund_cluster')->nullable();
            $table->string('accountable_officer')->nullable();
            $table->string('position')->nullable();
            $table->string('office')->default('CHR Region XII');
            $table->string('status')->default('draft'); // draft, completed
            $table->string('entity_name')->nullable()->default('Commission on Human Rights');
            $table->string('rc_code')->nullable();

            // Certification signatories
            $table->string('committee_chairman')->nullable();
            $table->string('committee_chairman_designation')->nullable();
            $table->string('committee_member1')->nullable();
            $table->string('committee_member1_designation')->nullable();
            $table->string('committee_member2')->nullable();
            $table->string('committee_member2_designation')->nullable();
            $table->string('head_of_agency')->nullable();
            $table->string('head_of_agency_designation')->nullable();
            $table->string('coa_representative')->nullable();
            $table->string('coa_representative_designation')->nullable();

            // Prepared by
            $table->string('prepared_by')->nullable();
            $table->string('prepared_by_designation')->nullable();

            // Noted by
            $table->string('noted_by')->nullable();
            $table->string('noted_by_designation')->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rpcis');
    }
};
