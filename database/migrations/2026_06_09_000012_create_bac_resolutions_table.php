<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bac_resolutions', function (Blueprint $table) {
            $table->id();
            $table->string('resolution_no')->unique();
            $table->string('title')->nullable();
            $table->text('preamble')->nullable();
            $table->text('operative_part')->nullable();
            $table->text('further_resolved')->nullable();
            $table->text('closing')->nullable();
            $table->date('date');
            $table->string('place')->nullable()->default('Koronadal City, Philippines');
            // Chairperson
            $table->string('chairperson_name')->nullable()->default('MIGUEL A. PEÑALOZA');
            $table->string('chairperson_designation')->nullable()->default('Chairperson');
            // Vice-Chairperson
            $table->string('vice_chairperson_name')->nullable()->default('ATTY. MAE P. GALONG');
            $table->string('vice_chairperson_designation')->nullable()->default('Vice-Chairperson');
            // Members
            $table->string('member1_name')->nullable()->default('ARNOLD B. AUMENTO');
            $table->string('member1_designation')->nullable()->default('Member');
            $table->string('member2_name')->nullable()->default('RIZALYN C. ISNANI-CONCHA');
            $table->string('member2_designation')->nullable()->default('Member');
            $table->string('member3_name')->nullable()->default('ATTY. REUBEN P. ESCARLAN');
            $table->string('member3_designation')->nullable()->default('Member');
            // Approval
            $table->string('approved_by_name')->nullable()->default('ATTY. KEYSIE M. GOMEZ');
            $table->string('approved_by_designation')->nullable()->default('Head of the Procuring Entity');
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bac_resolutions');
    }
};
