<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\RpciResource\Pages;
use App\Models\Rpci;
use App\Models\Supply;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table as FilamentTable;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class RpciResource extends Resource
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('rpcis.view');
    }

    public static function canCreate(): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('rpcis.create');
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('rpcis.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('rpcis.delete');
    }

    protected static ?string $model = Rpci::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 5;

    /**
     * Merge all active supplies into the given repeater state.
     *
     * Items already present in the RPCI (matched by supply_id) are updated in
     * place with the latest stock quantity and moving average cost from the
     * Supplies module, while the manually completed physical count fields
     * (on_hand_per_count, shortage/overage, remarks) are preserved. Supplies
     * not yet present are appended as new line items with the physical count
     * fields left blank.
     *
     * The returned array preserves the original state keys ("record-{id}" for
     * persisted items, UUIDs for new rows) so Filament updates existing rows
     * instead of recreating them.
     *
     * @param  array  $existingItems  Current repeater state keyed by item key
     * @param  Collection<int, Supply>|null  $supplies  Optional pre-fetched supplies
     * @return array{0: array, 1: int, 2: int} [merged items, added, updated]
     */
    public static function mergeActiveSuppliesIntoItems(array $existingItems = [], ?Collection $supplies = null): array
    {
        $supplies ??= Supply::with('category')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Index the existing rows by supply_id (first occurrence wins) so we
        // refresh them in place instead of ever creating a duplicate entry.
        $index = [];
        foreach ($existingItems as $key => $item) {
            $supplyId = $item['supply_id'] ?? null;
            if (is_numeric($supplyId) && ! isset($index[(string) $supplyId])) {
                $index[(string) $supplyId] = $key;
            }
        }

        $added = 0;
        $updated = 0;

        foreach ($supplies as $supply) {
            $payload = [
                'supply_id' => (string) $supply->id,
                'article' => $supply->category?->name ?? '',
                'description' => $supply->name,
                'stock_number' => $supply->stock_no,
                'unit_of_measure' => $supply->unit,
                'unit_value' => (float) (
                    $supply->unit_cost > 0
                        ? $supply->unit_cost
                        : ($supply->unit_price > 0 ? $supply->unit_price : 0)
                ),
                'balance_per_card' => (int) $supply->current_stock,
            ];

            $supplyId = (string) $supply->id;

            if (isset($index[$supplyId])) {
                $key = $index[$supplyId];

                // Refresh the supply-sourced fields, keep the manual count data.
                $existingItems[$key] = [
                    ...($existingItems[$key] ?? []),
                    ...$payload,
                ];
                $updated++;
            } else {
                // New line item — physical count fields intentionally blank.
                $existingItems[(string) Str::uuid()] = [
                    ...$payload,
                    'on_hand_per_count' => null,
                    'shortage_quantity' => null,
                    'shortage_value' => null,
                    'remarks' => '',
                ];
                $added++;
            }
        }

        // Collapse any pre-existing duplicate rows for the same supply so an
        // RPCI record never holds the same inventory item twice. The first
        // occurrence (already refreshed above) wins; later duplicates are
        // dropped and get cleaned up on the next save.
        $seen = [];
        $collapsed = [];
        foreach ($existingItems as $key => $item) {
            $supplyId = $item['supply_id'] ?? null;

            if (is_numeric($supplyId) && isset($seen[(string) $supplyId])) {
                continue;
            }

            if (is_numeric($supplyId)) {
                $seen[(string) $supplyId] = true;
            }

            $collapsed[$key] = $item;
        }
        $existingItems = $collapsed;

        // Re-sequence sort_order to match the on-screen order of the rows.
        $order = 1;
        foreach ($existingItems as &$item) {
            $item['sort_order'] = $order++;
        }
        unset($item);

        return [$existingItems, $added, $updated];
    }

    /**
     * Build the success notification message for the retrieval action.
     */
    public static function retrieveSummary(int $added, int $updated): string
    {
        $parts = [];

        if ($added > 0) {
            $parts[] = "Added {$added} new item(s)";
        }

        if ($updated > 0) {
            $parts[] = "Updated {$updated} existing item(s) with the latest stock and cost data";
        }

        if ($parts === []) {
            return 'All inventory items are already up to date.';
        }

        return implode(' and ', $parts) . ' from the Supplies module.';
    }

    protected static ?string $recordTitleAttribute = 'report_no';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ─── HEADER SECTION ─────────────────────────────────
                Forms\Components\Section::make('REPORT ON THE PHYSICAL COUNT OF INVENTORIES')
                    ->description('Appendix 66 — (Common-Office Supplies)')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('report_no')
                                    ->label('Report No.')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('inventory_type')
                                    ->label('Inventory Type')
                                    ->default('Common-Office Supplies')
                                    ->placeholder('Common-Office Supplies')
                                    ->required()
                                    ->columnSpan(1),
                                Forms\Components\DatePicker::make('report_date')
                                    ->label('Report Date (As of)')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('F j, Y')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('fund_cluster')
                                    ->label('Fund Cluster')
                                    ->placeholder('01')
                                    ->columnSpan(1)
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('accountable_person')
                                    ->label('Accountable Person')
                                    ->placeholder('Full name of accountable officer')
                                    ->required()
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('accountable_position')
                                    ->label('Position / Designation')
                                    ->placeholder('Supply Officer')
                                    ->columnSpan(1)
                                    ->required(),
                                Forms\Components\DatePicker::make('accountability_date')
                                    ->label('Accountability Date')
                                    ->placeholder('Date assumed accountability')
                                    ->displayFormat('F j, Y')
                                    ->columnSpan(1)
                                    ->required(),
                            ]),
                    ]),

                // ─── RETRIEVE INVENTORY ITEMS SECTION ───────────────
                Forms\Components\Section::make('Retrieve Inventory Items')
                    ->description('Load all active inventory items from the Supplies module as the initial listing for the physical count. Existing items are refreshed with the latest stock quantity and moving average cost; your physical count entries are preserved.')
                    ->schema([
                        Forms\Components\Placeholder::make('retrieve_info')
                            ->label('')
                            ->content('Click "Retrieve All Items" to import the latest inventory data. The physical count fields (On Hand Per Count, Shortage/Overage, Remarks) are left blank so they can be completed manually after the actual count.'),
                        Actions::make([
                            FormAction::make('retrieveAllItems')
                                ->label('Retrieve All Items')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('primary')
                                ->button()
                                ->action(function (Get $get, Set $set): void {
                                    $supplies = Supply::with('category')
                                        ->where('status', 'active')
                                        ->orderBy('name')
                                        ->get();

                                    if ($supplies->isEmpty()) {
                                        Notification::make()
                                            ->warning()
                                            ->title('No Inventory Items')
                                            ->body('No inventory items are available in the Supplies module.')
                                            ->send();

                                        return;
                                    }

                                    [$items, $added, $updated] = static::mergeActiveSuppliesIntoItems(
                                        $get('items') ?? [],
                                        $supplies,
                                    );

                                    $set('items', $items);

                                    Notification::make()
                                        ->success()
                                        ->title('Inventory Items Retrieved')
                                        ->body(static::retrieveSummary($added, $updated))
                                        ->send();
                                }),
                        ]),
                    ]),

                // ─── INVENTORY ITEMS SECTION ───────────────────────
                Forms\Components\Section::make('Inventory Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Grid::make(12)
                                    ->schema([
                                        Forms\Components\Select::make('supply_id')
                                            ->label('Supply Item')
                                            ->options(function () {
                                                return Supply::with('category')
                                                    ->where('status', 'active')
                                                    ->orderBy('name')
                                                    ->get()
                                                    ->mapWithKeys(fn ($s) => [
                                                        $s->id => "[{$s->stock_no}] {$s->name}"
                                                            . ($s->category ? " — {$s->category->name}" : ''),
                                                    ]);
                                            })
                                            ->searchable()
                                            ->live()
                                            ->distinct()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    /** @var \App\Models\Supply|null $supply */
                                                    $supply = Supply::with('category')->find($state);
                                                    if ($supply) {
                                                        $set('article', $supply->category?->name ?? '');
                                                        $set('description', $supply->name);
                                                        $set('stock_number', $supply->stock_no);
                                                        $set('unit_of_measure', $supply->unit);
                                                        $unitValue = (float) (
                                                            $supply->unit_cost > 0
                                                                ? $supply->unit_cost
                                                                : ($supply->unit_price > 0 ? $supply->unit_price : 0)
                                                        );
                                                        $set('unit_value', $unitValue);
                                                        $set('balance_per_card', (int) $supply->current_stock);
                                                    }
                                                }
                                            })
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('article')
                                            ->label('Article')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(1)
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('description')
                                            ->label('Description')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(2)
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('stock_number')
                                            ->label('Stock No.')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(1)
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('unit_of_measure')
                                            ->label('Unit of Measure')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(1)
                                            ->maxLength(50),
                                        Forms\Components\TextInput::make('unit_value')
                                            ->label('Unit Value')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefix('₱')
                                            ->default(0)
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('balance_per_card')
                                            ->label('Balance Per Card')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->default(0)
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('on_hand_per_count')
                                            ->label('On Hand Per Count')
                                            ->numeric()
                                            ->nullable()
                                            ->columnSpan(1)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                // No physical count recorded yet — keep the shortage fields blank.
                                                if ($state === null || $state === '') {
                                                    $set('shortage_quantity', null);
                                                    $set('shortage_value', null);

                                                    return;
                                                }

                                                $bpc = (int) ($get('balance_per_card') ?? 0);
                                                $qty = (int) $state;
                                                $diff = $bpc - $qty;
                                                $set('shortage_quantity', $diff);
                                                $set('shortage_value', round($diff * (float) ($get('unit_value') ?? 0), 2));
                                            }),
                                        Forms\Components\TextInput::make('shortage_quantity')
                                            ->label('S/O Qty')
                                            ->numeric()
                                            ->nullable()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('shortage_value')
                                            ->label('S/O Value')
                                            ->numeric()
                                            ->nullable()
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefix('₱')
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('remarks')
                                            ->label('Remarks')
                                            ->columnSpan(2)
                                            ->maxLength(255),
                                        Forms\Components\Hidden::make('sort_order'),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Item')
                            ->reorderableWithButtons()
                            ->cloneable()
                            ->collapsible(),
                    ]),

                // ─── CERTIFICATION SECTION ─────────────────────────
                Forms\Components\Section::make('Certification / Signatories')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Fieldset::make('Inventory Committee Chairman')
                                    ->schema([
                                        Forms\Components\TextInput::make('chairman_name')
                                            ->label('Name')
                                            ->maxLength(255)
                                            ->required(),
                                    ]),
                                Forms\Components\Fieldset::make('Inventory Committee Member 1')
                                    ->schema([
                                        Forms\Components\TextInput::make('member_one_name')
                                            ->label('Name')
                                            ->maxLength(255)
                                            ->required(),
                                    ]),
                                Forms\Components\Fieldset::make('Inventory Committee Member 2')
                                    ->schema([
                                        Forms\Components\TextInput::make('member_two_name')
                                            ->label('Name')
                                            ->maxLength(255)
                                            ->required(),
                                    ]),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Fieldset::make('Approved by (Head of Agency / Authorized Representative)')
                                    ->schema([
                                        Forms\Components\TextInput::make('approved_by')
                                            ->label('Name')
                                            ->maxLength(255)
                                            ->required(),
                                        Forms\Components\TextInput::make('approved_position')
                                            ->label('Designation')
                                            ->maxLength(255)
                                            ->required(),
                                    ]),
                                Forms\Components\Fieldset::make('Verified by (COA Representative)')
                                    ->schema([
                                        Forms\Components\TextInput::make('verified_by')
                                            ->label('Name')
                                            ->maxLength(255)
                                            ->required(),
                                        Forms\Components\TextInput::make('verified_position')
                                            ->label('Designation')
                                            ->maxLength(255)
                                            ->required(),
                                    ]),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('created_by')
                                    ->label('Created By')
                                    ->default(fn () => CurrentUser::get()?->name)
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('remarks')
                                    ->label('Remarks / Notes')
                                    ->columnSpan(1),
                            ]),
                    ]),

                // ─── STATUS SECTION ────────────────────────────────
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'finalized' => 'Finalized',
                            ])
                            ->default('draft')
                            ->disabled(fn (?Rpci $record): bool => $record?->isFinalized() ?? false)
                            ->required(),
                    ])
                    ->visible(fn (?Rpci $record): bool => $record !== null),
            ]);
    }

    public static function table(FilamentTable $table): FilamentTable
    {
        return $table
            ->columns([
                TextColumn::make('report_no')
                    ->label('Report No.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('inventory_type')
                    ->label('Inventory Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('report_date')
                    ->label('As of')
                    ->date('F j, Y')
                    ->sortable(),
                TextColumn::make('accountable_person')
                    ->label('Accountable Person')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'finalized' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                TextColumn::make('created_by')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'finalized' => 'Finalized',
                    ]),
                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('date_from'),
                        Forms\Components\DatePicker::make('date_to'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['date_from'], fn ($q) => $q->whereDate('report_date', '>=', $data['date_from']))
                        ->when($data['date_to'], fn ($q) => $q->whereDate('report_date', '<=', $data['date_to'])))
                    ->label('Date Range'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Rpci $record): bool =>
                        CurrentUser::get()?->hasPermissionTo('rpcis.update') && ! $record->isFinalized()),
                Tables\Actions\Action::make('print')
                    ->label('Print RPCI')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Rpci $record): string => route('rpci.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('finalize')
                    ->label('Finalize')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Rpci $record): bool =>
                        CurrentUser::get()?->hasPermissionTo('rpcis.update') && ! $record->isFinalized())
                    ->requiresConfirmation()
                    ->modalHeading('Finalize RPCI Report')
                    ->modalDescription('Are you sure you want to finalize this report? Once finalized, it cannot be edited or deleted.')
                    ->action(function (Rpci $record): void {
                        $record->update(['status' => 'finalized']);
                        Notification::make()
                            ->success()
                            ->title('Report Finalized')
                            ->body("RPCI {$record->report_no} has been finalized.")
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Rpci $record): bool =>
                        CurrentUser::get()?->hasPermissionTo('rpcis.delete') && ! $record->isFinalized()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('rpcis.delete')),
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
            'index' => Pages\ListRpcis::route('/'),
            'create' => Pages\CreateRpci::route('/create'),
            'view' => Pages\ViewRpci::route('/{record}'),
            'edit' => Pages\EditRpci::route('/{record}/edit'),
        ];
    }

    /**
     * Get the navigation badge showing total RPCI count.
     */
    public static function getNavigationBadge(): ?string
    {
        $total = static::getModel()::count();

        return $total > 0 ? (string) $total : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pending = static::getModel()::where('status', 'draft')->count();

        return $pending > 0 ? 'warning' : null;
    }
}
