<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\AbstractOfCanvass;
use App\Models\Category;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Support\TinFormatter;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('purchase_orders.view');
    }

    public static function canCreate(): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('purchase_orders.create')
            && static::hasEligibleSource();
    }

    /**
     * A Purchase Order can only be created when at least one Abstract of
     * Canvass exists that has not yet been converted into a Purchase Order.
     */
    public static function hasEligibleSource(): bool
    {
        $convertedAbcIds = PurchaseOrder::query()
            ->whereNotNull('abc_id')
            ->pluck('abc_id');

        return AbstractOfCanvass::query()
            ->whereNotIn('id', $convertedAbcIds)
            ->exists();
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('purchase_orders.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('purchase_orders.delete');
    }

    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Purchase Management';

    protected static ?int $navigationSort = 9;

    public static function getNavigationBadge(): ?string
    {
        $user = CurrentUser::get();
        if (! $user || ! $user->canManageProcurement()) {
            return null;
        }

        $count = AbstractOfCanvass::whereNotIn('id', function ($query) {
            $query->select('abc_id')
                ->from('purchase_orders')
                ->whereNotNull('abc_id');
        })->count();

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
                Forms\Components\Section::make('PURCHASE ORDER')
                    ->description('Document Control No. CHR-ROXII-ASD-FR-012')
                    ->schema([
                        // ABC selector — only show ABCs not yet linked to a PO
                        Forms\Components\Select::make('abc_id')
                            ->label('Source ABC')
                            ->placeholder('Select ABC to load winning supplier items...')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->options(function () {
                                $usedAbcIds = PurchaseOrder::pluck('abc_id')->filter()->unique();

                                return AbstractOfCanvass::with(['items', 'winningSupplier'])
                                    ->whereNotIn('id', $usedAbcIds)
                                    ->get()
                                    ->mapWithKeys(function ($abc) {
                                        $itemCount = $abc->items->count();
                                        $winner = $abc->winningSupplier?->name ?? $abc->recommendation ?? 'No winner';
                                        return [$abc->id => "{$abc->abc_no} → {$winner} ({$itemCount} items)"];
                                    });
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                /** @var \App\Models\AbstractOfCanvass|null $abc */
                                $abc = AbstractOfCanvass::with(['items', 'winningSupplier'])->find($state);
                                if (!$abc) {
                                    return;
                                }

                                // Determine winning supplier index via the FK relationship
                                $winningSupplier = $abc->winningSupplier;
                                $supplierNames = [
                                    trim($abc->supplier1_name ?? ''),
                                    trim($abc->supplier2_name ?? ''),
                                    trim($abc->supplier3_name ?? ''),
                                ];

                                $winnerIndex = 0; // default to first supplier
                                if ($winningSupplier) {
                                    // Match the winning supplier's name (case-insensitive) to find the column index
                                    $matchedIndex = array_search(
                                        mb_strtolower(trim($winningSupplier->name)),
                                        array_map('mb_strtolower', $supplierNames)
                                    );
                                    if ($matchedIndex !== false) {
                                        $winnerIndex = $matchedIndex;
                                    }
                                } elseif (!empty($abc->recommendation)) {
                                    // Fallback: match recommendation string to supplier name
                                    $matchedIndex = array_search(
                                        mb_strtolower(trim($abc->recommendation)),
                                        array_map('mb_strtolower', $supplierNames)
                                    );
                                    if ($matchedIndex !== false) {
                                        $winnerIndex = $matchedIndex;
                                    }
                                }

                                // Price column key in abc_items
                                $priceColumn = 'supplier' . ($winnerIndex + 1) . '_price';

                                // Set supplier info — prefer the FK-related supplier record
                                if ($winningSupplier) {
                                    $set('supplier_name', $winningSupplier->name);
                                    $set('supplier_id', $winningSupplier->id);
                                    $set('contact_number', $winningSupplier->phone);
                                    $set('address', $winningSupplier->address ?? '');
                                    if ($winningSupplier->tin) {
                                        $set('tin', $winningSupplier->tin);
                                    }
                                } else {
                                    $set('supplier_name', $supplierNames[$winnerIndex] ?? '');
                                    $supplier = \App\Services\AbstractOfCanvassService::resolveSupplierByName($supplierNames[$winnerIndex] ?? '');
                                    $set('supplier_id', $supplier?->id);
                                    $set('contact_number', $supplier?->phone);
                                    $set('address', $supplier?->address ?? '');
                                    if ($supplier?->tin) {
                                        $set('tin', $supplier->tin);
                                    }
                                }

                                // Load items from ABC using winning supplier prices
                                if ($abc->items->count() > 0) {
                                    $items = $abc->items->map(function ($abcItem) use ($priceColumn) {
                                        $unitCost = (float) ($abcItem->{$priceColumn} ?? 0);
                                        return [
                                            'stock_no' => $abcItem->stock_property_no ?? '',
                                            'unit' => $abcItem->unit ?? '',
                                            'description' => $abcItem->description ?? '',
                                            'quantity' => $abcItem->quantity ?? 1,
                                            'unit_cost' => $unitCost,
                                            'amount' => $unitCost * ($abcItem->quantity ?? 1),
                                        ];
                                    })->toArray();
                                    $livewire->data['items'] = $items;
                                }
                                // Compute initial total
                                self::recomputeTotal($livewire);
                            })
                            ->columnSpan(2),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('po_no')
                                    ->label('P.O. No.')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\DatePicker::make('date')
                                    ->label('Date')
                                    ->default(now())
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Supplier Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('supplier_name')
                                    ->label('Supplier')
                                    ->required(),
                                Forms\Components\TextInput::make('contact_number')
                                    ->label('Contact Number')
                                    ->required()
                                    ->tel()
                                    ->maxLength(13)
                                    ->regex('/^\+?\d+$/')
                                    ->validationMessages([
                                        'regex' => 'The Contact Number must contain numbers only.',
                                    ])
                                    ->extraInputAttributes([
                                        'oninput' => "this.value = this.value.replace(/[^\d+]/g, '').replace(/(?!^)\+/g, '').slice(0, 13)",
                                    ]),
                                Forms\Components\TextInput::make('address')
                                    ->label('Address')
                                    ->required(),
                                Forms\Components\TextInput::make('tin')
                                    ->label('TIN')
                                    ->required()
                                    ->maxLength(15)
                                    ->formatStateUsing(fn (?string $state): ?string => TinFormatter::format($state))
                                    ->dehydrateStateUsing(fn (?string $state): ?string => $state === null ? null : preg_replace('/\D/', '', $state))
                                    ->rule(function (): Closure {
                                        return function (string $attribute, mixed $value, Closure $fail): void {
                                            $value = (string) $value;

                                            // Let the 'required' rule handle empty values.
                                            if ($value === '') {
                                                return;
                                            }

                                            if (! preg_match('/^[\d-]+$/', $value)) {
                                                $fail('The TIN Number must contain numbers only.');

                                                return;
                                            }

                                            if (! preg_match('/^\d{9}$|^\d{12}$/', preg_replace('/\D/', '', $value))) {
                                                $fail('The TIN Number must be exactly 9 or 12 digits.');
                                            }
                                        };
                                    })
                                    ->extraInputAttributes([
                                        'oninput' => "let t=this.value.replace(/\D/g,'').slice(0,12);this.value=t.length?t.match(/.{1,3}/g).join('-'):'';",
                                    ]),
                                Forms\Components\TextInput::make('mode_of_procurement')
                                    ->label('Mode of Procurement'),
                                Forms\Components\Hidden::make('supplier_id'),
                            ]),
                    ]),

                Forms\Components\Section::make('Delivery Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('place_of_delivery')
                                    ->label('Place of Delivery'),
                                Forms\Components\TextInput::make('delivery_term')
                                    ->label('Delivery Term'),
                                Forms\Components\DatePicker::make('date_of_delivery')
                                    ->label('Date of Delivery'),
                                Forms\Components\TextInput::make('payment_term')
                                    ->label('Payment Term'),
                            ]),
                    ]),

                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Grid::make(6)
                                    ->schema([
                                        Forms\Components\TextInput::make('stock_no')
                                            ->label('Stock No.')
                                            ->columnSpan(1)
                                            ->hidden(),
                                        Forms\Components\TextInput::make('unit')
                                            ->label('Unit')
                                            ->columnSpan(1),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->rows(2)
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Qty/Term')
                                            ->numeric()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $get, callable $set, $livewire) {
                                                $qty = (float) ($state ?? 1);
                                                $cost = (float) ($get('unit_cost') ?? 0);
                                                $set('amount', round($qty * $cost, 2));
                                                self::recomputeTotal($livewire);
                                            })
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('unit_cost')
                                            ->label('Unit Cost')
                                            ->numeric()
                                            ->prefix('₱')
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $get, callable $set, $livewire) {
                                                $cost = (float) ($state ?? 0);
                                                $qty = (float) ($get('quantity') ?? 1);
                                                $set('amount', round($qty * $cost, 2));
                                                self::recomputeTotal($livewire);
                                            })
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('amount')
                                            ->label('Amount')
                                            ->numeric()
                                            ->prefix('₱')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(1),
                                        Forms\Components\Select::make('category_id')
                                            ->label('Category')
                                            ->relationship('category', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->columnSpan(1)
                                            ->hidden(),
                                    ]),
                            ])
                            ->defaultItems(5)
                            ->maxItems(30)
                            ->addActionLabel('Add Item')
                            ->afterStateUpdated(function ($state, $livewire) {
                                self::recomputeTotal($livewire);
                            }),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Overall Total Amount')
                                    ->numeric()
                                    ->prefix('₱')
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['style' => 'font-size:1.1em;font-weight:bold;']),
                                Forms\Components\TextInput::make('amount_in_words')
                                    ->label('Amount in Words')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),
                            ]),
                    ]),

                Forms\Components\Section::make('Signatories')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Fieldset::make('Conforme (Supplier)')
                                    ->schema([
                                        Forms\Components\TextInput::make('conforme_name')
                                            ->label('Signature Over Printed Name of Supplier')
                                            ->required(),
                                        Forms\Components\DatePicker::make('conforme_date')
                                            ->label('Date')
                                            ->hidden(),
                                    ]),
                                Forms\Components\Fieldset::make('Authorized Official')
                                    ->schema([
                                        Forms\Components\TextInput::make('authorized_official_name')
                                            ->label('Name')
                                            ->default('ATTY. KEYSIE M. GOMEZ'),
                                        Forms\Components\TextInput::make('authorized_official_designation')
                                            ->label('Designation')
                                            ->default('Director'),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Funds Available')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Fieldset::make('Funds Certification')
                                    ->schema([
                                        Forms\Components\TextInput::make('funds_available_by')
                                            ->label('Name')
                                            ->default('ANA FE B. GALANTO'),
                                        Forms\Components\TextInput::make('funds_available_designation')
                                            ->label('Designation')
                                            ->default('Admin Asst. II/Budget Officer'),
                                    ]),
                                Forms\Components\Fieldset::make('ALOBS')
                                    ->schema([
                                        Forms\Components\TextInput::make('alobs_no')
                                            ->label('ALOBS No.'),
                                        Forms\Components\TextInput::make('alobs_amount')
                                            ->label('Amount')
                                            ->numeric()
                                            ->prefix('₱'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['editingUser', 'items']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_no')
                    ->label('P.O. No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stockNumbers')
                    ->label('Stock No.')
                    ->searchable()
                    ->sortable()
                    ->hidden(),
                Tables\Columns\TextColumn::make('editingDisplayName')
                    ->label('Editing')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-lock-open')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('supplier_name')
                    ->label('Supplier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('abc.abc_no')
                    ->label('Source ABC')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('print')
                    ->label('Print PO')
                    ->icon('heroicon-o-printer')
                    ->url(fn (PurchaseOrder $record): string => route('po.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('purchase_orders.update')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('purchase_orders.delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('purchase_orders.delete')),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }

    /**
     * Recompute the overall total_amount from all items in the form data.
     * Called live when item quantities or unit costs change.
     */
    public static function recomputeTotal($livewire): void
    {
        $items = $livewire->data['items'] ?? [];
        $total = 0;
        foreach ($items as $item) {
            $total += (float) ($item['amount'] ?? 0);
        }
        $livewire->data['total_amount'] = round($total, 2);
    }
}
