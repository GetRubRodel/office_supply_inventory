<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Requisition;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('users.view');
    }

    public static function canCreate(): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('users.create');
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('users.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('users.delete');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::pending()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::pending()->exists() ? 'warning' : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->nullable(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('e.g., 09171234567'),
                        Forms\Components\Select::make('role')
                            ->options([
                                User::ROLE_ADMIN => 'Admin',
                                User::ROLE_STAFF => 'Staff',
                                User::ROLE_REGIONAL_DIRECTOR => 'Regional Director',
                                User::ROLE_DIVISION_CHIEF => 'Division Chief',
                                User::ROLE_SUPPLY_OFFICER => 'Supply Officer',
                                User::ROLE_PROPERTY_CUSTODIAN => 'Property Custodian',
                            ])
                            ->required()
                            ->default(User::ROLE_STAFF)
                            ->live(),
                        Forms\Components\Select::make('division')
                            ->label('Division')
                            ->options(array_combine(
                                array_keys(Requisition::DIVISION_MAP),
                                array_keys(Requisition::DIVISION_MAP)
                            ))
                            ->placeholder('Select Division')
                            ->visible(fn (Forms\Get $get): bool => in_array($get('role'), [
                                User::ROLE_STAFF,
                                User::ROLE_DIVISION_CHIEF,
                            ]))
                            ->nullable(),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->label(fn (string $context): string => $context === 'create' ? 'Password' : 'New Password (leave empty to keep current)')
                            ->maxLength(255)
                            ->dehydrated(fn ($state) => filled($state)),
                        Forms\Components\Select::make('status')
                            ->options([
                                User::STATUS_PENDING => 'Pending',
                                User::STATUS_APPROVED => 'Approved',
                                User::STATUS_REJECTED => 'Rejected',
                                User::STATUS_DEACTIVATED => 'Deactivated',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->native(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        User::ROLE_ADMIN => 'danger',
                        User::ROLE_STAFF => 'warning',
                        User::ROLE_REGIONAL_DIRECTOR => 'primary',
                        User::ROLE_DIVISION_CHIEF => 'info',
                        User::ROLE_SUPPLY_OFFICER => 'success',
                        User::ROLE_PROPERTY_CUSTODIAN => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => User::getRoleLabel($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('division')
                    ->label('Division')
                    ->badge()
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        User::STATUS_PENDING => 'warning',
                        User::STATUS_APPROVED => 'success',
                        User::STATUS_REJECTED => 'danger',
                        User::STATUS_DEACTIVATED => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        User::ROLE_ADMIN => 'Admin',
                        User::ROLE_STAFF => 'Staff',
                        User::ROLE_REGIONAL_DIRECTOR => 'Regional Director',
                        User::ROLE_DIVISION_CHIEF => 'Division Chief',
                        User::ROLE_SUPPLY_OFFICER => 'Supply Officer',
                        User::ROLE_PROPERTY_CUSTODIAN => 'Property Custodian',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        User::STATUS_PENDING => 'Pending',
                        User::STATUS_APPROVED => 'Approved',
                        User::STATUS_REJECTED => 'Rejected',
                        User::STATUS_DEACTIVATED => 'Deactivated',
                    ]),
            ])
            ->actions([
                // ── Status Management Actions ──────────────────────
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Approve User')
                    ->modalDescription(fn (User $record): string => "Are you sure you want to approve {$record->name}'s account? They will be able to log in immediately.")
                    ->action(function (User $record) {
                        $record->setStatus(User::STATUS_APPROVED);
                        Notification::make()
                            ->success()
                            ->title('User Approved')
                            ->body("{$record->name}'s account has been approved.")
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Reject User')
                    ->modalDescription(fn (User $record): string => "Are you sure you want to reject {$record->name}'s account? They will not be able to log in.")
                    ->action(function (User $record) {
                        $record->setStatus(User::STATUS_REJECTED);
                        Notification::make()
                            ->danger()
                            ->title('User Rejected')
                            ->body("{$record->name}'s account has been rejected.")
                            ->send();
                    }),

                Tables\Actions\Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->visible(fn (User $record): bool => $record->isApproved())
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate User')
                    ->modalDescription(fn (User $record): string => "Are you sure you want to deactivate {$record->name}'s account? They will lose access until reactivated.")
                    ->action(function (User $record) {
                        $record->setStatus(User::STATUS_DEACTIVATED);
                        Notification::make()
                            ->warning()
                            ->title('User Deactivated')
                            ->body("{$record->name}'s account has been deactivated.")
                            ->send();
                    }),

                Tables\Actions\Action::make('reactivate')
                    ->label('Reactivate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->isDeactivated())
                    ->requiresConfirmation()
                    ->modalHeading('Reactivate User')
                    ->modalDescription(fn (User $record): string => "Are you sure you want to reactivate {$record->name}'s account? They will regain access.")
                    ->action(function (User $record) {
                        $record->setStatus(User::STATUS_APPROVED);
                        Notification::make()
                            ->success()
                            ->title('User Reactivated')
                            ->body("{$record->name}'s account has been reactivated.")
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('approveSelected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $records->each(fn (User $record) => $record->setStatus(User::STATUS_APPROVED));
                            Notification::make()
                                ->success()
                                ->title('Users Approved')
                                ->body(count($records) . ' user(s) have been approved.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('rejectSelected')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $records->each(fn (User $record) => $record->setStatus(User::STATUS_REJECTED));
                            Notification::make()
                                ->danger()
                                ->title('Users Rejected')
                                ->body(count($records) . ' user(s) have been rejected.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
