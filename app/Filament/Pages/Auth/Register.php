<?php

namespace App\Filament\Pages\Auth;

use App\Models\Requisition;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        Forms\Components\Select::make('division')
                            ->label('Division')
                            ->options(array_combine(
                                array_keys(Requisition::DIVISION_MAP),
                                array_keys(Requisition::DIVISION_MAP)
                            ))
                            ->placeholder('Select your Division')
                            ->required(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    /**
     * New self-registered users default to 'pending' status and 'staff' role.
     * They must be approved by an admin before they can log in.
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        $data['role'] = User::ROLE_STAFF;
        $data['status'] = User::STATUS_PENDING;

        return $data;
    }

    /**
     * Do NOT auto-login after registration — admin must approve first.
     */
    protected function handleRegistration(array $data): Model
    {
        return $this->getUserModel()::create($data);
    }

    /**
     * Override register() to skip auto-login and redirect to login page.
     */
    public function register(): ?\Filament\Http\Responses\Auth\Contracts\RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (\DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function () {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();

            $this->callHook('afterRegister');

            return $user;
        });

        event(new \Filament\Events\Auth\Registered($user));

        // Send email verification if needed
        $this->sendEmailVerificationNotification($user);

        // Show success notification
        Notification::make()
            ->title('Registration Successful')
            ->body('Your account is pending administrator approval. You will be able to log in once an administrator approves your account.')
            ->success()
            ->send();

        // Redirect to login — do NOT auto-login
        $this->redirect(Filament::getLoginUrl());

        return null;
    }
}
