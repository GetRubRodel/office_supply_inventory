<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\RequisitionResource\Pages;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supply;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
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
        // Every authenticated role holds requisitions.create (granted in
        // RolePermissionSeeder), so every user can create and submit their
        // own RIS requests.
        return CurrentUser::get()?->hasPermissionTo('requisitions.create') ?? false;
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        if (! $user->hasPermissionTo('requisitions.update')) {
            return false;
        }

        // Staff and Regional Directors may only edit the
        // RIS requests they submitted themselves. Direct URL access to another
        // user's record is blocked here (403) and by RequisitionPolicy.
        if ($record instanceof Requisition
            && ($user->isStaff() || $user->isRegionalDirector())) {
            return $record->user_id === $user->id;
        }

        return true;
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('requisitions.delete');
    }

    protected static ?string $model = Requisition::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Staff see only the RIS requests they submitted themselves. All other
        // roles (Admin, Supply Officer, Division Chief,
        // Regional Director) continue to see the full list.
        $user = CurrentUser::get();
        if ($user && $user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = CurrentUser::get();

        // Regional Directors do not receive a requisition badge notification.
        if ($user && $user->isRegionalDirector()) {
            $count = static::getModel()::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    Requisition::STATUS_ISSUED,
                    Requisition::STATUS_PENDING_RECEIPT,
                ])
                ->count();

            return $count > 0 ? (string) $count : null;
        }

        // Administrator sees the number of RIS requests that have not yet
        // been issued (status is requested or approved). Once a request is
        // successfully issued it transitions out of these statuses and is
        // automatically removed from the pending count.
        if ($user && $user->isAdmin()) {
            $count = static::getModel()::query()
                ->whereIn('status', [
                    Requisition::STATUS_REQUESTED,
                    Requisition::STATUS_APPROVED,
                ])
                ->count();

            return $count > 0 ? (string) $count : null;
        }

        // Division Chief approves RIS requests, so they get a
        // "someone is requesting" badge: the number of requested RIS
        // documents currently awaiting approval. They also receive a badge
        // for their own issued-but-not-received RIS requests.
        if ($user && $user->isDivisionChief()) {
            $requestedCount = static::getModel()::query()
                ->where('status', Requisition::STATUS_REQUESTED)
                ->count();

            $ownIssuedCount = static::getModel()::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    Requisition::STATUS_ISSUED,
                    Requisition::STATUS_PENDING_RECEIPT,
                ])
                ->count();

            $count = $requestedCount + $ownIssuedCount;

            return $count > 0 ? (string) $count : null;
        }

        // Staff are notified when items they requested have been issued and
        // are waiting to be received/acknowledged. The badge counts only the
        // staff member's own RIS requests (user_id scoping) that have been
        // issued but not yet received — status `issued` or `pending_receipt`.
        //
        // The count is computed live from the requisitions table on every
        // page load, so it automatically increases the moment items are
        // issued (issue action moves the RIS to pending receipt) and
        // automatically drops off as soon as the staff member confirms
        // receipt (status becomes `received`). No manual database updates
        // are required. Each requisition row is counted at most once, so a
        // single RIS transaction can never produce a duplicate badge entry.
        // The same backend ownership scope also applies to the resource
        // query (getEloquentQuery) and the receive action
        // (User::canReceiveRequisition), so staff can never see or
        // acknowledge another user's issued RIS.
        if ($user && $user->isStaff()) {
            $count = static::getModel()::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    Requisition::STATUS_ISSUED,
                    Requisition::STATUS_PENDING_RECEIPT,
                ])
                ->count();

            return $count > 0 ? (string) $count : null;
        }

        // Supply Officers are notified the moment a RIS request is approved
        // by the Administrator or Division Chief and is therefore pending
        // their issuance action. The badge counts only RIS documents sitting
        // at the `approved` status (see Requisition::scopeAwaitingIssuance),
        // so requested, issued, received and cancelled requests are excluded.
        //
        // The count is computed live from the requisitions table on every page
        // load, so it appears automatically as soon as a request is approved
        // and drops off automatically as soon as the Supply Officer issues the
        // items (status becomes issued/pending_receipt) or the request is
        // cancelled. It is a single row-based count over distinct requisitions,
        // so the same RIS can never be counted twice no matter how many line
        // items it contains. This branch is reached only for the Supply Officer
        // role, so the badge is never shown to unauthorized roles.
        if ($user && $user->isSupplyOfficer()) {
            $count = static::getModel()::query()
                ->awaitingIssuance()
                ->count();

            return $count > 0 ? (string) $count : null;
        }

        // Pending issuance notification for other roles: RIS documents that
        // are approved (or already in the issuance pipeline) but still have
        // at least one item that has not been fully issued
        // (quantity_issued < quantity_requested). Requested (not yet
        // approved), cancelled, and fully issued/received documents are
        // excluded from the count.
        $count = static::getModel()::query()
            ->whereIn('status', [
                Requisition::STATUS_APPROVED,
                Requisition::STATUS_ISSUED,
                Requisition::STATUS_PENDING_RECEIPT,
            ])
            ->whereHas('items', fn ($q) => $q->whereRaw('COALESCE(quantity_issued, 0) < quantity_requested'))
            ->count();

        // Return null (no badge) when there is nothing pending to issue, so
        // the badge disappears once all pending RIS transactions are fully
        // issued.
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $user = CurrentUser::get();

        // Green for Staff and Regional Director — signals that their issued
        // items are ready to be received/acknowledged.
        if ($user && ($user->isStaff() || $user->isRegionalDirector())) {
            return 'success';
        }

        // Amber for the action-required workload badges: Admin / Division
        // Chief (awaiting approval or issuance) and the Supply Officer
        // (approved RIS awaiting issuance). Red for other roles'
        // pending-issuance backlog.
        return $user && ($user->isAdmin() || $user->isDivisionChief() || $user->isSupplyOfficer())
            ? 'warning'
            : 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $user = CurrentUser::get();

        if ($user && $user->isStaff()) {
            return 'RIS requests with issued items ready for your receipt';
        }

        if ($user && $user->isSupplyOfficer()) {
            return 'Approved RIS requests awaiting issuance';
        }

        if ($user && $user->isRegionalDirector()) {
            return 'RIS requests with issued items ready for your receipt';
        }

        if ($user && $user->isDivisionChief()) {
            return 'RIS requests awaiting approval or ready for your receipt';
        }

        return null;
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
                                    ->default('Commission on Human Rights RO-XII')
                                    ->columnSpan(2)
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('fund_cluster')
                                    ->label('Fund Cluster')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default('01'),
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
                                    ->columnSpan(2)
                                     ->required(),
                                Forms\Components\TextInput::make('responsibility_center_code')
                                    ->label('Responsibility Center Code')
                                    ->placeholder('Auto-generated on save')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),
                            ])->columns(3),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('office')
                                    ->label('Office')
                                    ->options([
                                        'CHR RO-XII' => 'CHR RO-XII',
                                        'COA XII' => 'COA XII',
                                            ])
                                            ->searchable()
                                            ->required(),
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
                                                    ->columnSpan(2),
                                                Forms\Components\TextInput::make('price')
                                                    ->label('Price')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->columnSpan(2),
                                                Forms\Components\TextInput::make('description')
                                                    ->label('Description')
                                                    ->columnSpan(2)
                                                    ->hidden(),
                                                Forms\Components\TextInput::make('quantity_requested')
                                                    ->label('Qty')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        $avail = $get('available_stock');
                                                        if ($avail !== null) {
                                                            $set('stock_available', $state <= $avail);
                                                        }
                                                    })
                                                    ->rules([
                                                        fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                            $avail = $get('available_stock');
                                                            if ($avail !== null && $value !== null && $value !== '') {
                                                                if ((int) $value > (int) $avail) {
                                                                    $fail('Insufficient stock. The requested quantity cannot exceed the available stock.');
                                                                }
                                                            }
                                                        },
                                                    ])
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
                                                    ->columnSpan(2)
                                                    ->hidden(),
                                                Forms\Components\TextInput::make('remarks')
                                                    ->label('Remarks')
                                                    ->columnSpan(2),
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->maxItems(20)
                            ->addActionLabel('Add Item')
                            ->collapsible(false),
                    ]),

                Forms\Components\Section::make('Purpose')
                    ->schema([
                        Forms\Components\Textarea::make('purpose')
                            ->rows(3)
                            ->columnSpanFull()
                            ->required(),
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
                                    ->default(fn () => CurrentUser::get()?->position)
                                    // Always display the requester's profile
                                    // position, never a role or a manually
                                    // maintained value.
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, ?Requisition $record): void {
                                        $component->state(
                                            $record?->requesterPosition() ?? CurrentUser::get()?->position
                                        );
                                    })
                                    // Force the resolved position on save so a
                                    // tampered form payload cannot persist a
                                    // hand-typed designation.
                                    ->dehydrateStateUsing(fn (?Requisition $record): ?string =>
                                        $record?->requesterPosition() ?? CurrentUser::get()?->position
                                    )
                                    ->disabled()
                                    ->dehydrated(true),
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
                        abort_unless(
                            CurrentUser::get()?->hasPermissionTo('requisitions.approve')
                            && CurrentUser::get()?->canApproveRequisition($record),
                            403
                        );
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
                    ->visible(fn (Requisition $record): bool => CurrentUser::get()?->canIssueRequisition($record) && $record->canTransitionTo(Requisition::STATUS_ISSUED))
                    ->requiresConfirmation()
                    ->modalHeading('Issue Items')
                    ->modalDescription('This will deduct the issued quantities from inventory and set the status to Pending Receipt. Are you sure?')
                    ->action(function (Requisition $record) {
                        $user = CurrentUser::get();
                        abort_unless($user && $user->canIssueRequisition($record), 403);

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

                                // Verify sufficient stock before deducting.
                                // lockForUpdate guards against concurrent issues
                                // over-deducting the same supply row.
                                $supply = Supply::whereKey($item->supply_id)->lockForUpdate()->firstOrFail();

                                 if ($qtyToIssue > $supply->current_stock) {
                                     throw new \RuntimeException(
                                         'Insufficient stock. The requested quantity cannot exceed the available stock.'
                                     );
                                 }

                                // Persist the actual issued quantity, mark stock as
                                // available, and snapshot the MAC at issue time so
                                // RSMI/inventory reports value the issue correctly
                                // even if the MAC changes later.
                                $item->update([
                                    'quantity_issued' => $qtyToIssue,
                                    'stock_available' => true,
                                    'issued_unit_cost' => $supply->unit_cost,
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

                // Receive: issued/pending_receipt → received (only original requester)
                Tables\Actions\Action::make('receive')
                    ->label('Receive')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->visible(fn (Requisition $record): bool =>
                        in_array($record->status, [Requisition::STATUS_ISSUED, Requisition::STATUS_PENDING_RECEIPT], true)
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
                        CurrentUser::get()?->canCancelRequisition($record)
                        && $record->canTransitionTo(Requisition::STATUS_APPROVED))
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Requisition')
                    ->modalDescription('Are you sure you want to cancel this requisition? This action cannot be undone. Any remarks you provide are sent to the requester as a notification.')
                    ->form([
                        Forms\Components\Textarea::make('cancellation_remarks')
                            ->label('Cancellation Remarks / Reason')
                            ->rows(4)
                            ->placeholder('Explain why this requisition is being cancelled...')
                            ->helperText('Shown to the original requester in the cancellation notification.'),
                    ])
                    ->action(function (Requisition $record, array $data) {
                        abort_unless(CurrentUser::get()?->canCancelRequisition($record), 403);
                        $record->transitionTo(
                            Requisition::STATUS_CANCELLED,
                            $data['cancellation_remarks'] ?? null,
                        );
                        Notification::make()
                            ->success()
                            ->title('Requisition Cancelled')
                            ->body("RIS {$record->ris_no} has been cancelled."
                                . ($record->user ? ' The requester has been notified.' : ''))
                            ->send();
                    }),

                Tables\Actions\Action::make('print')
                    ->label('Print RIS')
                    ->icon('heroicon-o-printer')
                    ->visible(fn (): bool => ! in_array(CurrentUser::get()?->role, [
                        User::ROLE_STAFF,
                        User::ROLE_DIVISION_CHIEF,
                        User::ROLE_REGIONAL_DIRECTOR,
                    ], true))
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
