<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /** Maximum OTP request attempts (send + resend) per window. */
    private const MAX_OTP_REQUESTS = 3;

    /** OTP request window in seconds (5 minutes). */
    private const OTP_REQUEST_WINDOW = 300;

    /** Maximum OTP verification attempts per window. */
    private const MAX_VERIFY_ATTEMPTS = 5;

    /** Verification attempt window in seconds. */
    private const VERIFY_ATTEMPT_WINDOW = 60;

    /** OTP lifetime in seconds. */
    private const OTP_TTL_SECONDS = 300;

    public function showRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = Str::lower($data['email']);
        $key = $this->requestKey($email, $request);

        if (RateLimiter::tooManyAttempts($key, self::MAX_OTP_REQUESTS)) {
            Log::warning('Password reset OTP rate limit hit', ['email' => $email, 'ip' => $request->ip()]);

            return back()->withErrors([
                'email' => 'Too many password reset requests. Please try again later.',
            ]);
        }

        // Consume a slot even for unknown accounts so the budget cannot be used to probe emails.
        RateLimiter::hit($key, self::OTP_REQUEST_WINDOW);

        // Opportunistic cleanup of expired OTPs.
        PasswordResetOtp::where('expires_at', '<', now())->delete();

        $user = User::where('email', $email)->first();

        if (! $user || ! $user->canLogin()) {
            // Do not reveal whether the email exists or whether the account is eligible.
            Log::info('Password reset OTP requested for unknown or ineligible account', [
                'email' => $email,
                'account_exists' => (bool) $user,
                'ip' => $request->ip(),
            ]);

            session(['otp_email' => $email]);

            return redirect()->route('password.otp.verify')
                ->with('status', 'If your email is registered and active, a verification code has been sent.');
        }

        $this->invalidatePreviousOtps($email);

        $otp = $this->generateOtp();

        PasswordResetOtp::create([
            'user_id' => $user->id,
            'email' => $email,
            'otp_code' => Hash::make($otp),
            'expires_at' => now()->addSeconds(self::OTP_TTL_SECONDS),
            'ip_address' => $request->ip(),
        ]);

        Mail::to($email)->send(new PasswordResetOtpMail($otp));

        Log::info('Password reset OTP sent', [
            'email' => $email,
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        session(['otp_email' => $email]);

        return redirect()->route('password.otp.verify')
            ->with('status', 'A 6-digit verification code has been sent to your email.');
    }

    public function showVerifyForm(): View|RedirectResponse
    {
        $email = session('otp_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-otp', ['email' => $email]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $email = session('otp_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'otp_code' => ['required', 'string', 'digits:6'],
        ]);

        $key = 'otp-verify:'.Str::lower($email).':'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_VERIFY_ATTEMPTS)) {
            Log::warning('Password reset OTP verification rate limit hit', ['email' => $email, 'ip' => $request->ip()]);

            return back()->withErrors([
                'otp_code' => 'Too many verification attempts. Please try again later.',
            ]);
        }

        RateLimiter::hit($key, self::VERIFY_ATTEMPT_WINDOW);

        $record = PasswordResetOtp::where('email', $email)->latest('id')->first();

        if (! $record) {
            Log::warning('Password reset OTP verification failed: no record', ['email' => $email, 'ip' => $request->ip()]);

            return back()->withErrors([
                'otp_code' => 'The verification code is invalid. Please request a new code.',
            ]);
        }

        if ($record->isExpired()) {
            Log::warning('Password reset OTP verification failed: expired', ['email' => $email, 'ip' => $request->ip()]);

            return back()->withErrors([
                'otp_code' => 'The verification code has expired. Please request a new code.',
            ]);
        }

        if ($record->is_used) {
            Log::warning('Password reset OTP verification failed: already used', ['email' => $email, 'ip' => $request->ip()]);

            return back()->withErrors([
                'otp_code' => 'The verification code has already been used. Please request a new code.',
            ]);
        }

        if (! Hash::check($data['otp_code'], $record->otp_code)) {
            Log::warning('Password reset OTP verification failed: code mismatch', ['email' => $email, 'ip' => $request->ip()]);

            return back()->withErrors([
                'otp_code' => 'The verification code is invalid. Please request a new code.',
            ]);
        }

        $record->update(['is_used' => true, 'used_at' => now()]);

        session(['otp_verified' => true]);

        Log::info('Password reset OTP verified', ['email' => $email, 'record_id' => $record->id, 'ip' => $request->ip()]);

        return redirect()->route('password.reset');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $email = session('otp_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $key = $this->requestKey($email, $request);

        if (RateLimiter::tooManyAttempts($key, self::MAX_OTP_REQUESTS)) {
            Log::warning('Password reset OTP resend rate limit hit', ['email' => $email, 'ip' => $request->ip()]);

            return back()->withErrors([
                'otp_code' => 'Too many password reset requests. Please try again later.',
            ]);
        }

        RateLimiter::hit($key, self::OTP_REQUEST_WINDOW);

        $user = User::where('email', $email)->first();

        if (! $user || ! $user->canLogin()) {
            return redirect()->route('password.request');
        }

        // A new code may only be requested after the previous one has expired.
        if (PasswordResetOtp::where('email', $email)->valid()->exists()) {
            return back()->withErrors([
                'otp_code' => 'A verification code is still active. Please wait for it to expire before requesting a new one.',
            ]);
        }

        $this->invalidatePreviousOtps($email);

        $otp = $this->generateOtp();

        PasswordResetOtp::create([
            'user_id' => $user->id,
            'email' => $email,
            'otp_code' => Hash::make($otp),
            'expires_at' => now()->addSeconds(self::OTP_TTL_SECONDS),
            'ip_address' => $request->ip(),
        ]);

        Mail::to($email)->send(new PasswordResetOtpMail($otp));

        // Un-verify the flow so a fresh code must be entered.
        session(['otp_verified' => false]);

        Log::info('Password reset OTP resent', ['email' => $email, 'user_id' => $user->id, 'ip' => $request->ip()]);

        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    public function showResetForm(): View|RedirectResponse
    {
        $email = session('otp_email');

        if (! $email || ! session('otp_verified')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', ['email' => $email]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $email = session('otp_email');

        if (! $email || ! session('otp_verified')) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('password.request');
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        // Invalidate every OTP issued for this account; the pruner removes them afterwards.
        $this->invalidatePreviousOtps($email);

        session()->forget(['otp_email', 'otp_verified']);

        Log::info('Password reset completed', [
            'email' => $email,
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('password.reset.success');
    }

    public function showSuccess(): View
    {
        return view('auth.password-reset-success');
    }

    private function requestKey(string $email, Request $request): string
    {
        return 'password-otp:'.Str::lower($email).':'.$request->ip();
    }

    private function invalidatePreviousOtps(string $email): void
    {
        PasswordResetOtp::where('email', $email)
            ->where('is_used', false)
            ->update(['is_used' => true, 'used_at' => now()]);
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
