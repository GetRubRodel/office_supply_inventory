<?php

namespace App\Filament\Pages\Auth;

use App\Models\Otp;
use App\Models\User;
use App\Notifications\SendOtp;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Locked;

class OtpPasswordReset extends SimplePage
{
    use InteractsWithFormActions;
    use WithRateLimiting;

    protected static string $view = 'filament.pages.auth.otp-password-reset';

    public int $step = 1;

    public ?string $identifier = null;
    public ?string $otp_input = null;
    public ?string $password = '';
    public ?string $passwordConfirmation = '';

    #[Locked]
    public ?string $verifiedIdentifier = null;

    public function mount(): void
    {
        // Retired: the secure OTP password reset flow now lives at the
        // standalone routes in routes/web.php (see ForgotPasswordController).
        redirect()->to(route('password.request'));
    }

    // ─── Step 1: Send OTP ───────────────────────────────────────

    public function request(): void
    {
        try {
            $this->rateLimit(3);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return;
        }

        $data = $this->form->getState();

        $identifier = trim($data['identifier']);

        // Determine if identifier is email or phone
        $type = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        // Find user by email or phone
        $user = User::where($type, $identifier)->first();

        if (! $user) {
            Notification::make()
                ->title('No account found with that ' . $type . '.')
                ->danger()
                ->send();
            return;
        }

        // Generate OTP
        $otp = Otp::generate($identifier, $type);

        // Send OTP notification
        if ($type === 'email') {
            $user->notify(new SendOtp($otp->token, 'email'));
        } else {
            // For phone, you would send SMS here.
            // For now, we store it so the user can see it.
            // In production, integrate with an SMS provider (Twilio, etc.)
            // $user->notify(new SendOtpSms($otp->token, 'phone'));

            // TODO: Replace with actual SMS gateway integration.
            // As a fallback, also send via email if user has email
            if ($user->email) {
                $user->notify(new SendOtp($otp->token, 'phone'));
            }
        }

        $this->identifier = $identifier;
        $this->step = 2;

        // Clear the form
        $this->form->fill();

        Notification::make()
            ->title('OTP sent to your ' . $type . '.')
            ->success()
            ->send();
    }

    // ─── Step 2: Verify OTP ─────────────────────────────────────

    public function verify(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return;
        }

        $data = $this->form->getState();
        $otpCode = $data['otp_input'] ?? null;

        if (! $otpCode || ! $this->identifier) {
            return;
        }

        $verified = Otp::verify($this->identifier, $otpCode);

        if (! $verified) {
            Notification::make()
                ->title('Invalid or expired OTP. Please try again.')
                ->danger()
                ->send();
            return;
        }

        $this->verifiedIdentifier = $this->identifier;
        $this->step = 3;
        $this->form->fill();
    }

    // ─── Step 3: Reset Password ─────────────────────────────────

    public function resetPassword(): void
    {
        try {
            $this->rateLimit(3);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return;
        }

        $data = $this->form->getState();

        if (! $this->verifiedIdentifier) {
            Notification::make()
                ->title('Session expired. Please start over.')
                ->danger()
                ->send();
            $this->step = 1;
            return;
        }

        $type = filter_var($this->verifiedIdentifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($type, $this->verifiedIdentifier)->first();

        if (! $user) {
            Notification::make()
                ->title('User not found.')
                ->danger()
                ->send();
            $this->step = 1;
            return;
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        // Log the user in
        Filament::auth()->login($user);

        session()->regenerate();

        Notification::make()
            ->title('Password reset successfully.')
            ->success()
            ->send();

        redirect()->intended(Filament::getUrl());
    }

    // ─── Form ───────────────────────────────────────────────────

    public function form(Form $form): Form
    {
        return $form;
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema($this->getStepSchema()),
            ),
        ];
    }

    protected function getStepSchema(): array
    {
        if ($this->step === 1) {
            return [$this->getIdentifierFormComponent()];
        }

        if ($this->step === 2) {
            return [$this->getOtpFormComponent()];
        }

        // Step 3
        return [
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ];
    }

    protected function getIdentifierFormComponent(): Component
    {
        return TextInput::make('identifier')
            ->label('Email Address or Phone Number')
            ->placeholder('e.g., user@example.com or 09171234567')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getOtpFormComponent(): Component
    {
        return TextInput::make('otp_input')
            ->label('Enter OTP Code')
            ->placeholder('6-digit code')
            ->required()
            ->numeric()
            ->length(6)
            ->autofocus();
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('New Password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(PasswordRule::default())
            ->same('passwordConfirmation')
            ->validationAttribute('password')
            ->autofocus();
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('Confirm New Password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);
    }

    // ─── Actions ────────────────────────────────────────────────

    protected function getFormActions(): array
    {
        if ($this->step === 1) {
            return [$this->getRequestFormAction()];
        }

        if ($this->step === 2) {
            return [$this->getVerifyFormAction()];
        }

        return [$this->getResetPasswordFormAction()];
    }

    protected function getRequestFormAction(): Action
    {
        return Action::make('request')
            ->label('Send OTP')
            ->submit('request');
    }

    protected function getVerifyFormAction(): Action
    {
        return Action::make('verify')
            ->label('Verify OTP')
            ->submit('verify');
    }

    public function getResetPasswordFormAction(): Action
    {
        return Action::make('resetPassword')
            ->label('Reset Password')
            ->submit('resetPassword');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    // ─── Navigation / Links ─────────────────────────────────────

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label('Back to login')
            ->icon(match (__('filament-panels::layout.direction')) {
                'rtl' => FilamentIcon::resolve('panels::pages.password-reset.request-password-reset.actions.login.rtl') ?? 'heroicon-m-arrow-right',
                default => FilamentIcon::resolve('panels::pages.password-reset.request-password-reset.actions.login') ?? 'heroicon-m-arrow-left',
            })
            ->url(filament()->getLoginUrl());
    }

    public function backAction(): Action
    {
        return Action::make('back')
            ->link()
            ->label('Go back')
            ->icon('heroicon-m-arrow-left')
            ->action(fn () => $this->step = 1);
    }

    // ─── Title ──────────────────────────────────────────────────

    public function getTitle(): string | Htmlable
    {
        return match ($this->step) {
            2 => 'Verify OTP',
            3 => 'Reset Password',
            default => 'Forgot Password',
        };
    }

    public function getHeading(): string | Htmlable
    {
        return $this->getTitle();
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title('Too many attempts')
            ->body("Please try again in {$exception->secondsUntilAvailable} seconds.")
            ->danger();
    }
}
