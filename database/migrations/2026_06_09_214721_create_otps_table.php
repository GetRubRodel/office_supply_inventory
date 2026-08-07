<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('identifier'); // email address or phone number
            $table->string('token', 6);   // 6-digit OTP
            $table->string('type')->default('email'); // 'email' or 'phone'
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index('identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
