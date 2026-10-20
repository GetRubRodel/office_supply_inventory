<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the retired `otps` table.
     *
     * The OTP password reset flow that used this table was retired in favour of
     * the standalone routes handled by ForgotPasswordController (which uses the
     * `password_reset_otps` table). The `Otp` model, `SendOtp` notification and
     * `OtpPasswordReset` page that referenced this table have been removed.
     */
    public function up(): void
    {
        Schema::dropIfExists('otps');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('otps', function ($table) {
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
};
