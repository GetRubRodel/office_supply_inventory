<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\PurchaseRequestResource\Pages;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Supply;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseRequestResource extends Resource
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('purchase_requests.view');
    }

    public static function canCreate(): bool
    {
        // The Create button and Create page are gated by the
        // purchase_requests.create permission. The Regional Director and
        // Division Chief hold view/approve/print only, so both the button
        // and direct URL access to the Create page are blocked for them.
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('purchase_requests.create');
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        if (! $user->hasPermissionTo('purchase_requests.update')) {
            return false;
        }

        // Staff may only edit their own purchase requests.
        if ($user->isOwnRecordsOnly() && $record && $record->user_id !== $user->id) {
            return false;
        }

        return true;
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        if (! $user->hasPermissionTo('purchase_requests.delete')) {
            return false;
        }

        // Staff may only delete their own purchase requests.
        if ($user->isOwnRecordsOnly() && $record && $record->user_id !== $user->id) {
            return false;
        }

        return true;
    }

    protected static ?string $model = PurchaseRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationGroup = 'Procurement Management';

    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        $user = CurrentUser::get();
        if (! $user || ! $user->canViewPurchaseNotifications()) {
            return null;
        }

        $count = static::getModel()::pending()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('PURCHASE REQUEST')
                    ->description('Document Control No. CHR-ROXII-ASD-FR-008')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Select::make('office_division')
                                    ->label('Office / Division')
                                    ->options([
                                        'Administrative Division' => 'Administrative Division',
                                        'Promotion and Advocacy Division' => 'Promotion and Advocacy Division',
                                        'Investigation Division' => 'Investigation Division',
                                        'Legal Division' => 'Legal Division',
                                    ])
                                    ->required()
                                    ->placeholder('Select Office / Division')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('pr_no')
                                    ->label('PR No.')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),
                                Forms\Components\DatePicker::make('date')
                                    ->label('Date')
                                    ->minDate(now()->startOfDay())
                                    ->required()
                                    ->columnSpan(1),
                            ]),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('rc_code')
                                    ->label('RC Code')
                                    ->default('3.1.2')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(2),
                            ]),
                    ]),

                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Grid::make(6)
                                    ->schema([
                                        Forms\Components\Select::make('supply_id')
                                            ->label('Item (from Supply Catalog)')
                                            ->options(Supply::with('category')->get()->mapWithKeys(function ($s) {
                                                $reorder = $s->reorder_level ? " (Reorder: {$s->reorder_level})" : '';
                                                return [$s->id => "[{$s->stock_no}] {$s->name} — {$s->unit}{$reorder}"];
                                            }))
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $supply = Supply::find($state);
                                                if ($supply) {
                                                    $set('stock_property_no', $supply->stock_no);
                                                    $set('unit', $supply->unit !== null ? trim($supply->unit) : null);
                                                    $set('item_description', $supply->name);
                                                    $set('reorder_level', $supply->reorder_level);
                                                } else {
                                                    $set('stock_property_no', null);
                                                    $set('unit', null);
                                                    $set('item_description', null);
                                                    $set('reorder_level', null);
                                                }
                                            })
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('stock_property_no')
                                            ->label('Stock/Property No.')
                                            ->hidden()
                                            ->columnSpan(1),
                                        Forms\Components\Select::make('unit')
                                            ->label('Unit')
                                            ->options(function () {
                                                $predefined = ['Piece', 'Box', 'Pack', 'Ream', 'Bottle', 'Roll', 'Pad', 'Tube', 'Gallon'];

                                                // Include any units already used by existing PR items or the
                                                // supply catalog so legacy values keep displaying correctly.
                                                $legacy = collect()
                                                    ->merge(PurchaseRequestItem::query()->whereNotNull('unit')->where('unit', '!=', '')->pluck('unit')->all())
                                                    ->merge(Supply::query()->whereNotNull('unit')->where('unit', '!=', '')->pluck('unit')->all())
                                                    ->map(fn ($unit): string => trim((string) $unit))
                                                    ->filter()
                                                    ->unique()
                                                    ->values();

                                                return collect($predefined)
                                                    ->merge($legacy)
                                                    ->unique()
                                                    ->mapWithKeys(fn (string $unit): array => [$unit => $unit]);
                                            })
                                            ->searchable()
                                            ->required()
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('reorder_level')
                                            ->label('Reorder Lvl')
                                            ->numeric()
                                            ->columnSpan(1)
                                            ->hidden(),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->required()
                                            ->columnSpan(1),
                                        Forms\Components\Textarea::make('item_description')
                                            ->label('Item Description')
                                            ->rows(2)
                                            ->required()
                                            ->columnSpan(5),
                                        Forms\Components\TextInput::make('unit_cost')
                                            ->label('Unit Cost')
                                            ->numeric()
                                            ->prefix('₱')
                                            ->placeholder('(optional)')
                                            ->columnSpan(1)
                                            ->hidden(),
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->maxItems(20)
                            ->addActionLabel('Add Item'),
                    ]),

                Forms\Components\Section::make('Project Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Code')
                                    ->default('5020301000')
                                    ->disabled()
                                    ->dehydrated(true),
                                Forms\Components\TextInput::make('name_of_project')
                                    ->label('Name of Project')
                                    ->required(),
                            ]),
                        Forms\Components\Textarea::make('purpose')
                            ->label('Purpose')
                            ->rows(2)
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('source_of_fund')
                                    ->label('Source of Fund')
                                    ->default('General Appropriations of Act (GAA)')
                                    ->disabled()
                                    ->dehydrated(true),
                                Forms\Components\TextInput::make('approved_budget')
                                    ->label('Approved Budget Allocation / APP')
                                    ->helperText('To be filled out by Supply Officer')
                                    ->numeric()
                                    ->prefix('₱'),
                            ]),
                    ]),

                Forms\Components\Section::make('Signatories')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Fieldset::make('Requested by')
                                    ->schema([
                                        Forms\Components\TextInput::make('requested_by_name')
                                            ->label('Name')
                                            ->default(fn () => CurrentUser::get()?->name)
                                            ->disabled()
                                            ->dehydrated(true),
                                        Forms\Components\TextInput::make('requested_by_designation')
                                            ->label('Designation')
                                            ->default(fn () => CurrentUser::get()?->position)
                                            ->dehydrated(true)
                                            ->disabled(),
                                    ]),
                                Forms\Components\Fieldset::make('Approved by')
                                    ->schema([
                                        Forms\Components\TextInput::make('approved_by_name')
                                            ->label('Name')
                                            ->default(fn () => CurrentUser::get()?->name)
                                            ->disabled()
                                            ->dehydrated(true),
                                        Forms\Components\TextInput::make('approved_by_designation')
                                            ->label('Designation')
                                            ->default(fn () => CurrentUser::get()?->position)
                                            ->disabled()
                                            ->dehydrated(true),
                                    ])
                                    ->visible(fn (?PurchaseRequest $record): bool => $record !== null),
                            ]),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                PurchaseRequest::STATUS_DRAFT      => 'Draft',
                                PurchaseRequest::STATUS_FOR_REVIEW => 'For Review',
                                PurchaseRequest::STATUS_APPROVED   => 'Approved',
                                PurchaseRequest::STATUS_REJECTED   => 'Rejected',
                                PurchaseRequest::STATUS_CANCELLED  => 'Cancelled',
                            ])
                            ->disabled()
                            ->dehydrated(true),
                    ])
                    ->visible(fn (?PurchaseRequest $record): bool => $record !== null),

                Forms\Components\Section::make('Rejection Details')
                    ->description('This Purchase Request was rejected. Review the remarks below.')
                    ->schema([
                        Forms\Components\Placeholder::make('rejected_status')
                            ->label('Status')
                            ->content('Rejected'),
                        Forms\Components\Placeholder::make('rejected_by')
                            ->label('Rejected By')
                            ->content(fn (?PurchaseRequest $record): string =>
                                $record && $record->rejected_by_name
                                    ? $record->rejected_by_name . ($record->rejected_by_designation ? " ({$record->rejected_by_designation})" : '')
                                    : '—'),
                        Forms\Components\Placeholder::make('rejected_at')
                            ->label('Rejection Date')
                            ->content(fn (?PurchaseRequest $record): string =>
                                $record && $record->rejected_at
                                    ? $record->rejected_at->format('F j, Y g:i A')
                                    : '—'),
                        Forms\Components\Placeholder::make('rejection_reason')
                            ->label('Rejection Remarks')
                            ->content(fn (?PurchaseRequest $record): string =>
                                $record && $record->rejection_reason ? $record->rejection_reason : '—'),
                    ])
                    ->columns(2)
                    ->visible(function (?PurchaseRequest $record): bool {
                        if (! $record || $record->status !== PurchaseRequest::STATUS_REJECTED) {
                            return false;
                        }

                        $user = CurrentUser::get();
                        if (! $user) {
                            return false;
                        }

                        // The original requester sees remarks for their own PRs.
                        if ($user->isOwnRecordsOnly()) {
                            return $record->user_id === $user->id;
                        }

                        // Approvers and other roles with view access may see rejection details.
                        return true;
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Every role holding purchase_requests.view sees the full PR list —
        // Staff track the whole procurement pipeline read-only (their write
        // access stays blocked by the policy and page guards).
        return parent::getEloquentQuery()->with('editingUser');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pr_no')
                    ->label('PR No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PurchaseRequest::STATUS_DRAFT      => 'gray',
                        PurchaseRequest::STATUS_FOR_REVIEW => 'warning',
                        PurchaseRequest::STATUS_APPROVED   => 'success',
                        PurchaseRequest::STATUS_REJECTED   => 'danger',
                        PurchaseRequest::STATUS_CANCELLED  => 'danger',
                        default                            => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        PurchaseRequest::STATUS_DRAFT      => 'Draft',
                        PurchaseRequest::STATUS_FOR_REVIEW => 'For Review',
                        PurchaseRequest::STATUS_APPROVED   => 'Approved',
                        PurchaseRequest::STATUS_REJECTED   => 'Rejected',
                        PurchaseRequest::STATUS_CANCELLED  => 'Cancelled',
                        default                            => ucfirst($state),
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('editingDisplayName')
                    ->label('Editing')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-lock-open')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('office_division')
                    ->label('Office')
                    ->searchable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('purpose')
                    ->limit(40),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        PurchaseRequest::STATUS_DRAFT      => 'Draft',
                        PurchaseRequest::STATUS_FOR_REVIEW => 'For Review',
                        PurchaseRequest::STATUS_APPROVED   => 'Approved',
                        PurchaseRequest::STATUS_REJECTED   => 'Rejected',
                        PurchaseRequest::STATUS_CANCELLED  => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                // Submit for Review: draft → for_review
                // Only the original requester or the Administrator may submit.
                Tables\Actions\Action::make('submit')
                    ->label('Submit for Review')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('warning')
                    ->visible(fn (PurchaseRequest $record): bool =>
                        CurrentUser::get()?->can('submit', $record)
                        && $record->canTransitionTo(PurchaseRequest::STATUS_FOR_REVIEW))
                    ->requiresConfirmation()
                    ->modalHeading('Submit Purchase Request')
                    ->modalDescription('Are you sure you want to submit this PR for review? It will no longer be editable.')
                    ->action(function (PurchaseRequest $record) {
                        abort_unless(CurrentUser::get()?->can('submit', $record), 403);
                        $record->transitionTo(PurchaseRequest::STATUS_FOR_REVIEW);
                        Notification::make()
                            ->success()
                            ->title('PR Submitted')
                            ->body("PR {$record->pr_no} has been submitted for review.")
                            ->send();
                    }),

                // Approve: for_review → approved
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (PurchaseRequest $record): bool =>
                        CurrentUser::get()?->hasPermissionTo('purchase_requests.approve') && $record->canTransitionTo(PurchaseRequest::STATUS_APPROVED))
                    ->requiresConfirmation()
                    ->modalHeading('Approve Purchase Request')
                    ->modalDescription('Are you sure you want to approve this PR? This action cannot be undone.')
                    ->action(function (PurchaseRequest $record) {
                        abort_unless(CurrentUser::get()?->hasPermissionTo('purchase_requests.approve'), 403);
                        $record->transitionTo(PurchaseRequest::STATUS_APPROVED);
                        Notification::make()
                            ->success()
                            ->title('PR Approved')
                            ->body("PR {$record->pr_no} has been approved.")
                            ->send();
                    }),

                // Reject: for_review → rejected (Admin, Regional Director)
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PurchaseRequest $record): bool =>
                        CurrentUser::get()?->hasPermissionTo('purchase_requests.approve')
                        && ! CurrentUser::get()?->isDivisionChief()
                        && $record->canTransitionTo(PurchaseRequest::STATUS_REJECTED))
                    ->requiresConfirmation()
                    ->modalHeading('Reject Purchase Request')
                    ->modalDescription('Please provide a reason for rejecting this PR. The requester will be notified.')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Remarks / Reason')
                            ->required()
                            ->rows(4)
                            ->placeholder('Explain why this purchase request is being rejected...')
                            ->helperText('This will be shown to the requester.'),
                    ])
                    ->action(function (PurchaseRequest $record, array $data) {
                        abort_unless(CurrentUser::get()?->hasPermissionTo('purchase_requests.approve'), 403);
                        $record->transitionTo(PurchaseRequest::STATUS_REJECTED, $data['rejection_reason']);

                        Notification::make()
                            ->success()
                            ->title('PR Rejected')
                            ->body("PR {$record->pr_no} has been rejected.")
                            ->send();
                    }),

                // Revise (re-open for editing): rejected → draft
                Tables\Actions\Action::make('revise')
                    ->label('Revise')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->visible(fn (PurchaseRequest $record): bool =>
                        CurrentUser::get()?->hasPermissionTo('purchase_requests.update')
                        && $record->canTransitionTo(PurchaseRequest::STATUS_DRAFT))
                    ->requiresConfirmation()
                    ->modalHeading('Revise Purchase Request')
                    ->modalDescription('Re-open this PR for editing as a draft.')
                    ->action(function (PurchaseRequest $record) {
                        $record->transitionTo(PurchaseRequest::STATUS_DRAFT);
                        Notification::make()
                            ->info()
                            ->title('PR Re-opened')
                            ->body("PR {$record->pr_no} has been returned to draft for revision.")
                            ->send();
                    }),

                // Cancel: pending approval → cancelled (Admin, Regional Director)
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-stop-circle')
                    ->color('danger')
                    ->visible(fn (PurchaseRequest $record): bool =>
                        (CurrentUser::get()?->isAdmin() || CurrentUser::get()?->isRegionalDirector())
                        && $record->canTransitionTo(PurchaseRequest::STATUS_APPROVED))
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Purchase Request')
                    ->modalDescription('Are you sure you want to cancel this PR? This action cannot be undone.')
                    ->action(function (PurchaseRequest $record) {
                        abort_unless(
                            CurrentUser::get()?->isAdmin() || CurrentUser::get()?->isRegionalDirector(),
                            403
                        );
                        $record->transitionTo(PurchaseRequest::STATUS_CANCELLED);
                        Notification::make()
                            ->success()
                            ->title('PR Cancelled')
                            ->body("PR {$record->pr_no} has been cancelled.")
                            ->send();
                    }),

                Tables\Actions\Action::make('print')
                    ->label('Print PR')
                    ->icon('heroicon-o-printer')
                    ->visible(fn (): bool => ! CurrentUser::get()?->isRegionalDirector() && ! CurrentUser::get()?->isStaff() && ! CurrentUser::get()?->isDivisionChief())
                    ->url(fn (PurchaseRequest $record): string => route('pr.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (PurchaseRequest $record): bool =>
                        CurrentUser::get()?->hasPermissionTo('purchase_requests.update')
                        && ! CurrentUser::get()?->isSupplyOfficer()
                        && $record->status === PurchaseRequest::STATUS_DRAFT),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (PurchaseRequest $record): bool =>
                        CurrentUser::get()?->hasPermissionTo('purchase_requests.delete')
                        && ! CurrentUser::get()?->isSupplyOfficer()
                        && (CurrentUser::get()?->isAdmin() || $record->status === PurchaseRequest::STATUS_DRAFT)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('purchase_requests.delete')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseRequests::route('/'),
            'create' => Pages\CreatePurchaseRequest::route('/create'),
            'view' => Pages\ViewPurchaseRequest::route('/{record}'),
            'edit' => Pages\EditPurchaseRequest::route('/{record}/edit'),
        ];
    }
}
