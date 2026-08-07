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

class PurchaseRequestResource extends Resource
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('purchase_requests.view');
    }

    public static function canCreate(): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('purchase_requests.create');
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('purchase_requests.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('purchase_requests.delete');
    }

    protected static ?string $model = PurchaseRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationGroup = 'Purchase Management';

    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        $user = CurrentUser::get();
        if (! $user) return '0';

        $query = static::getModel()::pending();

        if ($user->isOwnRecordsOnly()) {
            $query->where('user_id', $user->id);
        }
        // Division Chief and all other roles see all pending PRs.

        return (string) $query->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() !== '0' ? 'warning' : null;
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
                                        'Commission on Human Rights XII' => 'Commission on Human Rights XII',
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
                                    ->default(now())
                                    ->required()
                                    ->columnSpan(1),
                            ]),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('rc_code')
                                    ->label('RC Code')
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
                            ->defaultItems(5)
                            ->maxItems(20)
                            ->addActionLabel('Add Item'),
                    ]),

                Forms\Components\Section::make('Project Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Code'),
                                Forms\Components\TextInput::make('name_of_project')
                                    ->label('Name of Project')
                                    ->required(),
                            ]),
                        Forms\Components\Textarea::make('purpose')
                            ->label('Purpose')
                            ->rows(3)
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('source_of_fund')
                                    ->label('Source of Fund'),
                                Forms\Components\TextInput::make('approved_budget')
                                    ->label('Approved Budget Allocation / APP')
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
                                            ->label('Position')
                                            ->dehydrated(true)
                                            ->required(),
                                    ]),
                                Forms\Components\Fieldset::make('Approved by')
                                    ->schema([
                                        Forms\Components\TextInput::make('approved_by_name')
                                            ->label('Name')
                                            ->default(fn () => CurrentUser::get()?->name)
                                            ->dehydrated(true),
                                        Forms\Components\TextInput::make('approved_by_designation')
                                            ->label('Position')
                                            ->default(fn () => CurrentUser::get()?->role)
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
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with('editingUser');
        $user = CurrentUser::get();
        if (! $user) return $query;

        if ($user->isOwnRecordsOnly()) {
            $query->where('user_id', $user->id);
        }
        // Division Chief and all other roles see all Purchase Requests.

        return $query;
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
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('office_division')
                    ->label('Office')
                    ->searchable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('purpose')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Actions\Action::make('submit')
                    ->label('Submit for Review')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('warning')
                    ->visible(fn (PurchaseRequest $record): bool =>
                        CurrentUser::get()?->hasPermissionTo('purchase_requests.update')
                        && $record->canTransitionTo(PurchaseRequest::STATUS_FOR_REVIEW))
                    ->requiresConfirmation()
                    ->modalHeading('Submit Purchase Request')
                    ->modalDescription('Are you sure you want to submit this PR for review? It will no longer be editable.')
                    ->action(function (PurchaseRequest $record) {
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

                // Reject: for_review → rejected (Admin only)
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
                    ->modalDescription('Are you sure you want to reject this PR? It will be sent back to draft.')
                    ->action(function (PurchaseRequest $record) {
                        abort_unless(CurrentUser::get()?->hasPermissionTo('purchase_requests.approve'), 403);
                        $record->transitionTo(PurchaseRequest::STATUS_REJECTED);
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
                        && $record->status === PurchaseRequest::STATUS_DRAFT),
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
