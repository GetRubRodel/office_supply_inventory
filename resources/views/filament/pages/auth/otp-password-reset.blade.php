<x-filament-panels::page.simple>
    <x-slot name="subheading">
        {{ $this->loginAction }}
    </x-slot>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_PASSWORD_RESET_REQUEST_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="{{ $this->step === 1 ? 'request' : ($this->step === 2 ? 'verify' : 'resetPassword') }}">
        {{ $this->form }}

        @if ($this->step === 2)
            <div class="flex justify-center mt-2">
                <button type="button" wire:click="request" class="text-sm text-primary-600 hover:text-primary-500">
                    Resend OTP
                </button>
            </div>
        @endif

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    <div class="text-center text-sm text-gray-500 dark:text-gray-400 mt-4">
        @if ($this->step === 2)
            Enter the 6-digit code sent to your {{ filter_var($this->identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone' }}.
        @elseif ($this->step === 1)
            Enter your registered email or phone number to receive an OTP.
        @endif
    </div>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_PASSWORD_RESET_REQUEST_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
</x-filament-panels::page.simple>
