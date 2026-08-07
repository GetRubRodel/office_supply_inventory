<?php

namespace App\Filament\Pages;

use App\Support\CurrentUser;

use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'My Profile';

    protected static ?string $title = 'My Profile';

    protected static ?string $slug = 'profile';

    protected static ?int $navigationSort = -2;

    protected static string $view = 'filament.pages.user-profile';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return CurrentUser::check();
    }

    public function mount(): void
    {
        $this->form->fill(CurrentUser::get()?->toArray() ?? []);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Profile Information')
                    ->description('Update your account details below.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('username')
                            ->label('Username')
                            ->nullable()
                            ->maxLength(255)
                            ->unique(table: User::class, ignorable: fn () => CurrentUser::get()),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, ignorable: fn () => CurrentUser::get()),
                        TextInput::make('phone')
                            ->label('Contact Number')
                            ->tel()
                            ->nullable()
                            ->maxLength(13)
                            ->regex('/^\+?\d+$/')
                            ->validationMessages([
                                'regex' => 'The Contact Number must contain numbers only.',
                            ])
                            ->extraInputAttributes([
                                'oninput' => "this.value = this.value.replace(/[^\d+]/g, '').replace(/(?!^)\+/g, '').slice(0, 13)",
                            ])
                            ->placeholder('e.g., 09171234567'),
                    ])->columns(2),
                Section::make('Change Password')
                    ->description('Leave blank to keep your current password.')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->revealable()
                            ->currentPassword()
                            ->requiredWith('new_password'),
                        TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->revealable()
                            ->confirmed()
                            ->requiredWith('current_password')
                            ->rule(Password::defaults()),
                        TextInput::make('new_password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->revealable()
                            ->requiredWith('new_password'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var \App\Models\User $user */
        $user = CurrentUser::get();

        $user->name = $data['name'];
        $user->username = $data['username'] ?? null;
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;

        if (filled($data['current_password'] ?? null)) {
            $user->password = Hash::make($data['new_password']);
        }

        $user->save();

        // Re-fill the form with fresh user data (excluding password fields)
        $this->form->fill($user->toArray());

        Notification::make()
            ->success()
            ->title('Profile Updated')
            ->body('Your profile information has been saved successfully.')
            ->send();
    }
}
