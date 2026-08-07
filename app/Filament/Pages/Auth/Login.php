<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (\DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        // First, check if the user exists and what their status is
        $user = User::where('email', $data['email'])->first();

        if ($user && ! $user->canLogin()) {
            // Don't attempt authentication — show status message instead
            Filament::auth()->logout();

            $message = match ($user->status) {
                User::STATUS_PENDING => 'Your account is pending administrator approval. Please wait until your account has been approved before logging in.',
                User::STATUS_REJECTED => 'Your account has been rejected. Please contact the administrator for further assistance.',
                User::STATUS_DEACTIVATED => 'Your account has been deactivated. Please contact the administrator to reactivate your account.',
                default => 'Your account cannot access the system at this time.',
            };

            Notification::make()
                ->title('Access Denied')
                ->body($message)
                ->warning()
                ->send();

            return null;
        }

        // Proceed with normal authentication
        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof \Filament\Models\Contracts\FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/login.form.password.label'))
            ->hint(new HtmlString(Blade::render('<x-filament::link :href="route(\'password.request\')" tabindex="3"> {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>')))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }
}
