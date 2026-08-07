<?php

namespace App\Console\Commands;

use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SendTestMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {to=test@example.com : Recipient email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test password reset OTP email through MailHog to verify local mail capture';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('to');

        $user = User::firstWhere('email', $email);
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        if ($user) {
            PasswordResetOtp::create([
                'user_id' => $user->id,
                'email' => $email,
                'otp_code' => Hash::make($otp),
                'expires_at' => now()->addSeconds(5),
                'ip_address' => '127.0.0.1',
            ]);
        }

        Mail::to($email)->send(new PasswordResetOtpMail($otp));

        $this->info("Test OTP email sent to {$email}");
        $this->info("Generated OTP: {$otp}");

        return self::SUCCESS;
    }
}
