<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\BacResolution;
use App\Models\PurchaseOrder;
use App\Services\ProcurementBadgeService;
use App\Support\TinFormatter;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

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
            && static::hasEligibleBacResolution();
    }

    /**
     * A Purchase Order can only be created when at least one BAC Resolution
     * exists that has not yet been converted into a Purchase Order.
     */
    public static function hasEligibleBacResolution(): bool
    {
        return ProcurementBadgeService::eligibleQuery(ProcurementBadgeService::STAGE_PO)->exists();
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        // Division Chief may not edit Purchase Order records.
        if ($user->isDivisionChief()) {
            return false;
        }

        return $user->hasPermissionTo('purchase_orders.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        // Supply Officer may not delete PO records.
        if ($user->isSupplyOfficer()) {
            return false;
        }

        return $user->hasPermissionTo('purchase_orders.delete');
    }

    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Procurement Management';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        return ProcurementBadgeService::badgeFor(ProcurementBadgeService::STAGE_PO, CurrentUser::get());
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * Default values for the Purchase Order's fixed-choice fields.
     *
     * Filament only applies ->default() when the create form is hydrated
     * with no state, so the very same defaults are re-applied to records
     * that stored nothing for them before the edit form is filled
     * (see EditPurchaseOrder::mutateFormDataBeforeFill()).
     */
    public const FIELD_DEFAULTS = [
        'mode_of_procurement' => 'Small Value Procurement (SVP)',
        'delivery_term' => 'Within 7 Calendar Days After Receipt of Purchase Order',
        'payment_term' => 'Full Payment Upon Delivery, Inspection, and Acceptance',
        'funds_available_by' => 'ANA FE B. GALANTO',
        'funds_available_designation' => 'Admin Asst. II/Budget Officer',
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('PURCHASE ORDER')
                    ->description('Document Control No. CHR-ROXII-ASD-FR-012')
                    ->schema([
                        // BAC Resolution selector — only show BAC Resolutions
                        // not yet converted into a Purchase Order
                        Forms\Components\Select::make('bac_resolution_id')
                            ->label('Source BAC Resolution')
                            ->placeholder('Select a BAC Resolution to load its items...')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->options(function (?PurchaseOrder $record): array {
                                return BacResolution::query()
                                    ->with('abc')
                                    ->where(function (Builder $query) use ($record) {
                                        $query->eligibleForPo();

                                        // Keep the already-linked BAC Resolution
                                        // selectable so the label resolves on edit.
                                        if ($record?->bac_resolution_id) {
                                            $query->orWhere('id', $record->bac_resolution_id);
                                        }
                                    })
                                    ->orderByDesc('created_at')
                                    ->get()
                                    ->mapWithKeys(function (BacResolution $bac) {
                                        $itemCount = count(is_array($bac->items_snapshot) ? $bac->items_snapshot : []);
                                        $converted = $bac->purchaseOrders()->exists() ? ' — Converted' : '';
                                        $supplier = $bac->supplier_name ?? '—';

                                        return [$bac->id => "{$bac->resolution_no} — {$supplier} ({$itemCount} items){$converted}"];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->live()
                            ->validationMessages([
                                'required' => 'Please select the BAC Resolution to convert.',
                            ])
                            ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                /** @var \App\Models\BacResolution|null $bac */
                                $bac = BacResolution::with('abc')->find($state);
                                if (!$bac) {
                                    return;
                                }

                                // Preserve the PO → ABC relationship (reports
                                // traverse PO → ABC → RFQ → PR) by deriving it
                                // from the source BAC Resolution's own ABC.
                                $set('abc_id', $bac->abc_id);

                                // Auto-populate supplier name and conforme name
                                // from the BAC Resolution's supplier (ABC winning
                                // bid supplier). These fields are read-only.
                                $supplierName = $bac->supplier_name;
                                if ($supplierName) {
                                    $set('supplier_name', $supplierName);
                                    $set('conforme_name', $supplierName);
                                }

                                // Auto-populate the authorized official (approver)
                                // from the BAC Resolution's approved-by section.
                                $approvedByName = $bac->approved_by_name;
                                if ($approvedByName) {
                                    $set('authorized_official_name', $approvedByName);
                                }

                                // Load items from the BAC Resolution snapshot
                                // (captured from its source ABC winning bids).
                                $snapshot = is_array($bac->items_snapshot) ? $bac->items_snapshot : [];

                                if ($snapshot !== []) {
                                    $livewire->data['items'] = collect($snapshot)->map(function (array $item) {
                                        return [
                                            'stock_no' => $item['stock_no'] ?? '',
                                            'unit' => $item['unit'] ?? '',
                                            'description' => $item['description'] ?? '',
                                            'quantity' => $item['quantity'] ?? 1,
                                            'unit_cost' => (float) ($item['unit_cost'] ?? 0),
                                            'amount' => (float) ($item['total'] ?? 0),
                                        ];
                                    })->all();
                                }

                                // Compute initial total
                                self::recomputeTotal($livewire);
                            })
                            ->columnSpan(2),
                        Forms\Components\Hidden::make('abc_id'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('po_no')
                                    ->label('P.O. No.')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\DatePicker::make('date')
                                    ->label('Date')
                                    ->minDate(now()->startOfDay())
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Supplier Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('supplier_name')
                                    ->label('Supplier')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
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
                                Forms\Components\Select::make('mode_of_procurement')
                                    ->label('Mode of Procurement')
                                    ->options([
                                        'Small Value Procurement (SVP)' => 'Small Value Procurement (SVP)',
                                        'Direct Acquisition' => 'Direct Acquisition',
                                        'Repeat Order' => 'Repeat Order',
                                        'Direct Sales' => 'Direct Sales',
                                    ])
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Hidden::make('supplier_id'),
                            ]),
                    ]),

                Forms\Components\Section::make('Delivery Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('place_of_delivery')
                                    ->label('Place of Delivery')
                                    ->default('CHR Regional Office XII, Carpenter Hill, Koronadal City')
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\Select::make('delivery_term')
                                    ->label('Delivery Term')
                                    ->options([
                                        'Within 7 Calendar Days After Receipt of Purchase Order'
                                           => 'Within 7 Calendar Days After Receipt of Purchase Order',
                                        'Within 15 Calendar Days After Receipt of Purchase Order'
                                           => 'Within 15 Calendar Days After Receipt of Purchase Order',
                                        'Within 30 Calendar Days After Receipt of Purchase Order'
                                           => 'Within 30 Calendar Days After Receipt of Purchase Order',
                                   ])
                                   ->searchable()
                                   ->required(),
                                Forms\Components\DatePicker::make('date_of_delivery')
                                    ->label('Date of Delivery')
                                    ->minDate(now()->startOfDay()),
                                Forms\Components\Select::make('payment_term')
                                    ->label('Payment Term')
                                    ->options([
                                        'Full Payment Upon Delivery, Inspection, and Acceptance'
                                           => 'Full Payment Upon Delivery, Inspection, and Acceptance',
                                        'Payment Within 30 Calendar Days After Complete Delivery and Acceptance'
                                           => 'Payment Within 30 Calendar Days After Complete Delivery and Acceptance',
                                        'Payment Upon Submission of Complete Billing Documents'
                                           => 'Payment Upon Submission of Complete Billing Documents',
                                   ])
                                   ->searchable()
                                   ->required(),
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
                                            ->columnSpan(1)
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->rows(2)
                                            ->columnSpan(1)
                                            ->disabled()
                                            ->dehydrated(),
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
                                            ->columnSpan(1)
                                            ->disabled()
                                            ->dehydrated(),
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
                                            ->columnSpan(1)
                                            ->disabled()
                                            ->dehydrated(),
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
                            ->defaultItems(0)
                            ->maxItems(30)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): ?array => static::filterEmptyItem($data))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): ?array => static::filterEmptyItem($data))
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
                                            ->required()
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\DatePicker::make('conforme_date')
                                            ->label('Date')
                                            ->hidden(),
                                    ]),
                                Forms\Components\Fieldset::make('Authorized Official')
                                    ->schema([
                                        Forms\Components\TextInput::make('authorized_official_name')
                                            ->label('Name')
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('authorized_official_designation')
                                            ->label('Designation')
                                            ->default('Authorized Official')
                                            ->disabled()
                                            ->dehydrated(),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Funds Available')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Fieldset::make('Funds Certification')
                                    ->schema([
                                        Forms\Components\Select::make('funds_available_by')
                                            ->label('Name')
                                            ->options([
                                                'ANA FE B. GALANTO' => 'ANA FE B. GALANTO',
                                                'CHERRIE LYN C. OLACO' => 'CHERRIE LYN C. OLACO',
                                            ])
                                            ->default(self::FIELD_DEFAULTS['funds_available_by'])
                                            ->required(),
                                        Forms\Components\Select::make('funds_available_designation')
                                            ->label('Designation')
                                            ->options([
                                                'Admin Asst. II/Budget Officer' => 'Admin Asst. II/Budget Officer',
                                                'Admin Asst. III/Budget Officer' => 'Admin Asst. III/Budget Officer',
                                            ])
                                            ->default(self::FIELD_DEFAULTS['funds_available_designation'])
                                            ->required(),
                                    ]),
                                Forms\Components\Fieldset::make('ALOBS')
                                    ->schema([
                                        Forms\Components\TextInput::make('alobs_no')
                                            ->label('ALOBS No.'),
                                        Forms\Components\TextInput::make('alobs_amount')
    ->label('Amount')
    ->prefix('₱')
    ->formatStateUsing(fn ($state) => $state !== null
        ? number_format((float) $state, 2, '.', ',')
        : null
    )
    ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['editingUser', 'items', 'bacResolution']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_no')
                    ->label('P.O. No.')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('bacResolution.resolution_no')
                    ->label('Source BAC Res.'),
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
                    ->placeholder('—')
                    ->hidden(),
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
                    ->counts('items')
                    ->hidden(),
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
                    ->visible(fn (): bool => ! CurrentUser::get()?->isRegionalDirector() && ! CurrentUser::get()?->isStaff() && ! CurrentUser::get()?->isDivisionChief())
                    ->url(fn (PurchaseOrder $record): string => route('po.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('purchase_orders.update') && ! CurrentUser::get()?->isDivisionChief()),
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

    /**
     * Skip blank Repeater rows (e.g. the default empty items) when saving the
     * relationship, so no junk rows or NOT NULL violations are produced.
     */
    private static function filterEmptyItem(array $data): ?array
    {
        $hasContent = filled($data['description'] ?? null)
            || filled($data['quantity'] ?? null)
            || filled($data['unit_cost'] ?? null);

        return $hasContent ? $data : null;
    }

    /**
     * Backend guard (create flow): validate that submitted items match the
     * BAC Resolution's items_snapshot exactly. On create the items are
     * auto-loaded from the snapshot, so any deviation — extra rows, removed
     * rows, or altered values — is a crafted request and is rejected.
     *
     * A BAC Resolution whose source ABC carries no line items exposes an
     * empty snapshot; there is then no source data to deviate from, so the
     * guard has nothing to compare and lets the submitted rows through.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function validateItemsMatchBacSnapshot(array $items, int|string $bacResolutionId): void
    {
        /** @var BacResolution|null $bac */
        $bac = BacResolution::find($bacResolutionId);

        if (! $bac) {
            return;
        }

        $snapshot = is_array($bac->items_snapshot) ? $bac->items_snapshot : [];

        if ($snapshot === []) {
            return;
        }

        // Filter out empty items (same logic as filterEmptyItem)
        $submittedItems = array_values(array_filter($items, function (array $item) {
            return filled($item['description'] ?? null)
                || filled($item['quantity'] ?? null)
                || filled($item['unit_cost'] ?? null);
        }));

        $snapshotCount = count($snapshot);
        $submittedCount = count($submittedItems);

        if ($submittedCount !== $snapshotCount) {
            throw ValidationException::withMessages([
                'items' => "Item count mismatch: expected {$snapshotCount} items from the BAC Resolution, but received {$submittedCount}. Items cannot be added or removed manually.",
            ]);
        }

        // Validate each submitted item matches the corresponding snapshot entry
        foreach ($submittedItems as $index => $item) {
            $expected = $snapshot[$index] ?? [];

            $fields = ['stock_no', 'unit', 'description', 'quantity', 'unit_cost'];
            foreach ($fields as $field) {
                $submitted = (string) ($item[$field] ?? '');
                $expectedValue = (string) ($expected[$field] ?? '');

                // Normalize numeric comparisons
                if (in_array($field, ['quantity', 'unit_cost'], true)) {
                    if (abs((float) $submitted - (float) $expectedValue) > 0.001) {
                        throw ValidationException::withMessages([
                            'items' => "Item #{$index} field '{$field}' does not match the BAC Resolution source data. Items cannot be modified manually.",
                        ]);
                    }
                } elseif ($submitted !== $expectedValue) {
                    throw ValidationException::withMessages([
                        'items' => "Item #{$index} field '{$field}' does not match the BAC Resolution source data. Items cannot be modified manually.",
                    ]);
                }
            }
        }
    }

    /**
     * Backend guard (edit flow): validate that the submitted item count
     * matches the count of items already attached to the Purchase Order.
     * This blocks any crafted request from injecting extra items (or
     * silently dropping existing ones) while still allowing legitimate
     * edits of legacy records whose stored items may differ from the
     * current BAC snapshot.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function validateItemsCount(array $items, int $expectedCount): void
    {
        $submittedItems = array_values(array_filter($items, function (array $item) {
            return filled($item['description'] ?? null)
                || filled($item['quantity'] ?? null)
                || filled($item['unit_cost'] ?? null);
        }));

        $submittedCount = count($submittedItems);

        if ($submittedCount !== $expectedCount) {
            throw ValidationException::withMessages([
                'items' => "Item count mismatch: expected {$expectedCount} items, but received {$submittedCount}. Items cannot be added or removed manually.",
            ]);
        }
    }
}
