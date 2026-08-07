<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('password-reset-otp:prune', function () {
    $deleted = \App\Models\PasswordResetOtp::where('expires_at', '<', now()->subHour())->delete();
    $this->info("Pruned {$deleted} expired password reset OTP(s).");
})->purpose('Delete expired password reset OTPs');

Schedule::command('password-reset-otp:prune')->everyMinute();
