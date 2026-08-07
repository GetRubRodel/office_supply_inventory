<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\RequisitionResource\Pages;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supply;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RequisitionResource extends Resource
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('requisitions.view');
    }

    public static function canCreate(): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('requisitions.create');
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('requisitions.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('requisitions.delete');
    }

    protected static ?string $model = Requisition::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = CurrentUser::get();
        if (! $user) return $query;

        if ($user->isOwnRecordsOnly()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = CurrentUser::get();
        if (! $user) return '0';

        $query = static::getModel()::whereIn('status', ['requested', 'pending_receipt']);

        if ($user->isOwnRecordsOnly()) {
            $query->where('user_id', $user->id);
        }
        // Division Chief and all other roles see all RIS records.

        return (string) $query->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $user = CurrentUser::get();
        if (! $user) return null;

        return static::getNavigationBadge() !== '0' ? 'warning' : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('REQUISITION AND ISSUE SLIP')
                    ->description('Appendix 63')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('entity_name')
                                    ->label('Entity Name')
                                    ->placeholder('__________________________________')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('fund_cluster')
                                    ->label('Fund Cluster')
                                    ->placeholder('______________________'),
                            ])->columns(3),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('division')
                                    ->label('Division')
                                    ->placeholder('Select Division')
                                    ->options(array_combine(
                                        array_keys(Requisition::DIVISION_MAP),
                                        array_keys(Requisition::DIVISION_MAP)
                                    ))
                                    ->searchable()
                                    ->live()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('responsibility_center_code')
                                    ->label('Responsibility Center Code')
                                    ->placeholder('Auto-generated on save')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),
                            ])->columns(3),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('office')
                                    ->label('Office')
                                    ->placeholder('________________________________________________'),
                                Forms\Components\TextInput::make('ris_no')
                                    ->label('RIS No.')
                                    ->placeholder('Auto-generated')
                                    ->disabled()
                                    ->dehydrated(false),
                            ])->columns(2),
                    ]),

                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Hidden::make('available_stock')
                                    ->default(0),

                                        Forms\Components\Grid::make(12)
                                            ->schema([
                                                Forms\Components\TextInput::make('stock_no')
                                                    ->label('Stock No.')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->columnSpan(2),
                                                Forms\Components\Select::make('supply_id')
                                                    ->label('Item')
                                                    ->options(Supply::pluck('name', 'id'))
                                                    ->searchable()
                                                    ->live()
                                                    ->afterStateHydrated(function (callable $set, $state, $context) {
                                                        if ($context === 'edit' && $state) {
                                                            $supply = Supply::find($state);
                                                            if ($supply) {
                                                                $set('available_stock', $supply->current_stock);
                                                                $set('price', $supply->unit_cost);
                                                            }
                                                        }
                                                    })
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $supply = Supply::find($state);
                                                        if ($supply) {
                                                            $set('stock_no', $supply->stock_no);
                                                            $set('unit', $supply->unit);
                                                            $set('description', $supply->name);
                                                            $set('available_stock', $supply->current_stock);
                                                            $set('price', $supply->unit_cost);
                                                            $set('stock_available', 1 <= $supply->current_stock);
                                                        } else {
                                                            $set('stock_no', null);
                                                            $set('unit', null);
                                                            $set('description', null);
                                                            $set('available_stock', 0);
                                                            $set('price', null);
                                                            $set('stock_available', false);
                                                        }
                                                    })
                                                    ->columnSpan(2),
                                                Forms\Components\TextInput::make('unit')
                                                    ->label('Unit')
                                                    ->columnSpan(1),
                                                Forms\Components\TextInput::make('price')
                                                    ->label('Price')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->columnSpan(1),
                                                Forms\Components\TextInput::make('description')
                                                    ->label('Description')
                                                    ->columnSpan(2)
                                                    ->hidden(),
                                                Forms\Components\TextInput::make('quantity_requested')
                                                    ->label('Qty')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(0)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        $avail = $get('available_stock');
                                                        if ($avail !== null) {
                                                            $set('stock_available', $state <= $avail);
                                                        }
                                                    })
                                                    ->helperText(function (callable $get) {
                                                        $qty = $get('quantity_requested');
                                                        $avail = $get('available_stock');
                                                        $unit = $get('unit') ?? 'units';
                                                        if ($qty !== null && $qty !== '' && $avail !== null) {
                                                            if ((int) $qty <= (int) $avail) {
                                                                return '✅ Available: ' . $avail . ' ' . $unit;
                                                            }
                                                            return '❌ Insufficient — only ' . $avail . ' ' . $unit . ' available';
                                                        }
                                                        return null;
                                                    })
                                                    ->columnSpan(1),
                                                Forms\Components\Checkbox::make('stock_available')
                                                    ->label('Stock?')
                                                    ->disabled()
                                                    ->columnSpan(1),
                                                Forms\Components\TextInput::make('quantity_issued')
                                                    ->label('Issue Qty')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->columnSpan(1)
                                                    ->hidden(),
                                                Forms\Components\TextInput::make('remarks')
                                                    ->label('Remarks')
                                                    ->columnSpan(1),
                                    ]),
                            ])
                            ->defaultItems(5)
                            ->maxItems(20)
                            ->addActionLabel('Add Item')
                            ->collapsible(false),
                    ]),

                Forms\Components\Section::make('Purpose')
                    ->schema([
                        Forms\Components\Textarea::make('purpose')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Requesting Party')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('requested_by_name')
                                    ->label('Requested by')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->default(fn () => CurrentUser::get()?->name),
                                Forms\Components\TextInput::make('requested_by_designation')
                                    ->label('Designation')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->default(fn () => CurrentUser::get()?->role),
                                Forms\Components\DatePicker::make('requested_by_date')
                                    ->label('Date Requested')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->default(fn () => now()),
                            ]),
                    ]),

                Forms\Components\Section::make('Status')
                    ->hidden()
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'requested' => 'Requested',
                                'approved' => 'Approved',
                                'issued' => 'Issued',
                                'pending_receipt' => 'Pending Receipt',
                                'received' => 'Received',
                                'cancelled' => 'Cancelled',
                            ])
                            ->disabled()
                            ->dehydrated(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ris_no')
                    ->label('RIS No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entity_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('division')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('office')
                    ->searchable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'requested' => 'warning',
                        'approved' => 'success',
                        'issued' => 'info',
                        'pending_receipt' => 'warning',
                        'received' => 'primary',
                        'cancelled' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_receipt' => 'Pending Receipt',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                Tables\Columns\TextColumn::make('requested_by_name')
                    ->label('Requested by')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if (! $user) return;

                // Staff: own records only
                if ($user->isOwnRecordsOnly()) {
                    $query->where('user_id', $user->id);
                    return;
                }

                // Division Chief and all other roles see all RIS records (no filter).
            })
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'requested' => 'Requested',
                        'approved' => 'Approved',
                        'issued' => 'Issued',
                        'pending_receipt' => 'Pending Receipt',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                // --- Workflow Actions (status-dependent) ---

                // Approve: requested → approved
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Requisition $record): bool => CurrentUser::get()?->canApproveRequisition($record) && $record->canTransitionTo(Requisition::STATUS_APPROVED))
                    ->requiresConfirmation()
                    ->modalHeading('Approve Requisition')
                    ->modalDescription('Are you sure you want to approve this requisition? This action cannot be undone.')
                    ->action(function (Requisition $record) {
                        abort_unless(CurrentUser::get()?->hasPermissionTo('requisitions.approve'), 403);
                        $record->transitionTo(Requisition::STATUS_APPROVED);
                        Notification::make()
                            ->success()
                            ->title('Requisition Approved')
                            ->body("RIS {$record->ris_no} has been approved.")
                            ->send();
                    }),

                // Issue: approved → issued → pending_receipt (deducts stock)
                Tables\Actions\Action::make('issue')
                    ->label('Issue')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->visible(fn (Requisition $record): bool => CurrentUser::get()?->hasPermissionTo('requisitions.issue') && $record->canTransitionTo(Requisition::STATUS_ISSUED))
                    ->requiresConfirmation()
                    ->modalHeading('Issue Items')
                    ->modalDescription('This will deduct the issued quantities from inventory and set the status to Pending Receipt. Are you sure?')
                    ->action(function (Requisition $record) {
                        abort_unless(CurrentUser::get()?->hasPermissionTo('requisitions.issue'), 403);

                        DB::transaction(function () use ($record) {
                            foreach ($record->items as $item) {
                                if (! $item->supply_id) {
                                    continue;
                                }

                                // Use the user-specified issue qty, or fall back to requested qty
                                $qtyToIssue = (int) ($item->quantity_issued ?: $item->quantity_requested);

                                if ($qtyToIssue <= 0) {
                                    continue;
                                }

                                // Verify sufficient stock before deducting
                                $supply = Supply::findOrFail($item->supply_id);

                                if ($qtyToIssue > $supply->current_stock) {
                                    throw new \RuntimeException(
                                        "Insufficient stock for \"{$supply->name}\". " .
                                        "Required: {$qtyToIssue}, Available: {$supply->current_stock} {$supply->unit}."
                                    );
                                }

                                // Persist the actual issued quantity and mark stock as available
                                $item->update([
                                    'quantity_issued' => $qtyToIssue,
                                    'stock_available' => true,
                                ]);

                                // Deduct current stock and total_cost (unit_cost/MAC unchanged)
                                $supply->issueStock($qtyToIssue, $record->ris_no);
                            }

                            // Transition to issued (records who issued)
                            $record->transitionTo(Requisition::STATUS_ISSUED);
                        });

                        // Auto-transition to pending_receipt
                        $record->transitionTo(Requisition::STATUS_PENDING_RECEIPT);

                        // Notify the requester
                        $requester = $record->user;
                        if ($requester) {
                            $requester->notify(new \App\Notifications\RisReadyForReceipt($record));
                        }

                        Notification::make()
                            ->success()
                            ->title('Items Issued')
                            ->body("RIS {$record->ris_no} has been issued and set to Pending Receipt. The requester has been notified.")
                            ->send();
                    }),

                // Receive: pending_receipt → received (only original requester)
                Tables\Actions\Action::make('receive')
                    ->label('Receive')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->visible(fn (Requisition $record): bool =>
                        $record->status === Requisition::STATUS_PENDING_RECEIPT
                        && CurrentUser::get()?->canReceiveRequisition($record))
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Receipt')
                    ->modalDescription('Confirm that the requested items have been received by the requester.')
                    ->action(function (Requisition $record) {
                        $user = CurrentUser::get();
                        abort_unless($user && $user->canReceiveRequisition($record), 403);
                        $record->transitionTo(Requisition::STATUS_RECEIVED);
                        Notification::make()
                            ->success()
                            ->title('Items Received')
                            ->body("RIS {$record->ris_no} has been marked as received. The transaction is now complete.")
                            ->send();
                    }),

                // Cancel: pending approval → cancelled (Admin, Division Chief for own division)
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Requisition $record): bool =>
                        CurrentUser::get()?->canApproveRequisition($record)
                        && $record->canTransitionTo(Requisition::STATUS_APPROVED))
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Requisition')
                    ->modalDescription('Are you sure you want to cancel this requisition? This action cannot be undone.')
                    ->action(function (Requisition $record) {
                        abort_unless(CurrentUser::get()?->canApproveRequisition($record), 403);
                        $record->transitionTo(Requisition::STATUS_CANCELLED);
                        Notification::make()
                            ->success()
                            ->title('Requisition Cancelled')
                            ->body("RIS {$record->ris_no} has been cancelled.")
                            ->send();
                    }),

                Tables\Actions\Action::make('print')
                    ->label('Print RIS')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Requisition $record): string => route('ris.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Requisition $record): bool =>
                        CurrentUser::get()?->hasPermissionTo('requisitions.update')
                        && $record->status === 'requested'
                        && ($record->user_id === CurrentUser::id()
                            || CurrentUser::get()?->isDivisionChief()
                            || CurrentUser::get()?->isAdmin())),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Requisition $record): bool =>
                        CurrentUser::get()?->hasPermissionTo('requisitions.delete')
                        && ($record->status === 'requested' && $record->user_id === CurrentUser::id())),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('requisitions.delete')),
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
            'index' => Pages\ListRequisitions::route('/'),
            'create' => Pages\CreateRequisition::route('/create'),
            'view' => Pages\ViewRequisition::route('/{record}'),
            'edit' => Pages\EditRequisition::route('/{record}/edit'),
        ];
    }
}
