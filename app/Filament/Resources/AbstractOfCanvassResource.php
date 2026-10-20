<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\AbstractOfCanvassResource\Pages;
use App\Models\AbstractOfCanvass;
use App\Models\RequestForQuotation;
use App\Services\AbstractOfCanvassService;
use App\Services\ProcurementBadgeService;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AbstractOfCanvassResource extends Resource
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('abstracts_of_canvass.view');
    }

    public static function canCreate(): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('abstracts_of_canvass.create')
            && static::hasEligibleSource();
    }

    /**
     * An ABC can only be created when at least one Request for Quotation
     * exists that has not yet been converted into an Abstract of Canvass.
     */
    public static function hasEligibleSource(): bool
    {
        return ProcurementBadgeService::eligibleQuery(ProcurementBadgeService::STAGE_ABC)->exists();
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        // Division Chief may not edit Abstract of Canvass records.
        if ($user->isDivisionChief()) {
            return false;
        }

        return $user->hasPermissionTo('abstracts_of_canvass.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        // Supply Officer may not delete ABC records.
        if ($user->isSupplyOfficer()) {
            return false;
        }

        return $user->hasPermissionTo('abstracts_of_canvass.delete');
    }

    protected static ?string $model = AbstractOfCanvass::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Procurement Management';

    protected static ?int $navigationSort = 8;

    public static function getNavigationBadge(): ?string
    {
        return ProcurementBadgeService::badgeFor(ProcurementBadgeService::STAGE_ABC, CurrentUser::get());
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ABSTRACT OF BIDS & CANVASS')
                    ->description('Document Control No. CHR-ROXII-ASD-FR-006')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('abc_no')
                                    ->label('ABC No.')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),
                                Forms\Components\DatePicker::make('date_of_advertisement')
                                    ->label('Date of Advertisement')
                                    ->minDate(now()->startOfDay())
                                    ->columnSpan(1)
                                    ->required(),
                                Forms\Components\DatePicker::make('date_of_opening')
                                    ->label('Date of Opening')
                                    ->minDate(now()->startOfDay())
                                    ->columnSpan(1)
                                    ->required(),
                            ]),
                        Forms\Components\Select::make('rfq_id')
                            ->label('Source RFQ')
                            ->placeholder('Select RFQ to load items...')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->options(function (?AbstractOfCanvass $record): array {
                                return RequestForQuotation::query()
                                    ->with('items')
                                    ->where(function (Builder $query) use ($record) {
                                        $query->eligibleForAbc();

                                        // Keep the already-linked RFQ selectable so the
                                        // label resolves on edit/view pages.
                                        if ($record?->rfq_id) {
                                            $query->orWhere('id', $record->rfq_id);
                                        }
                                    })
                                    ->get()
                                    ->mapWithKeys(function ($rfq) {
                                        $itemCount = $rfq->items->count();
                                        return [$rfq->id => "{$rfq->rfq_no} ({$itemCount} items)"];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                /** @var \App\Models\RequestForQuotation|null $rfq */
                                $rfq = RequestForQuotation::with('items')->find($state);
                                if ($rfq && $rfq->items->count() > 0) {
                                    // values() re-indexes the collection so item_number
                                    // is sequential (1, 2, 3…) instead of the RFQ item PK.
                                    $items = $rfq->items->values()->map(fn($item, $idx) => [
                                        'item_number' => $idx + 1,
                                        'unit' => $item->unit,
                                        'quantity' => $item->quantity,
                                        'description' => $item->item_description,
                                        'supplier1_price' => null,
                                        'supplier2_price' => null,
                                        'supplier3_price' => null,
                                    ])->toArray();
                                    $livewire->data['items'] = $items;
                                } else {
                                    // A source RFQ without items (or no RFQ selected)
                                    // must not leave stale rows behind when the
                                    // selection changes.
                                    $livewire->data['items'] = [];
                                }
                                if ($rfq) {
                                    // Approval details always come from the source RFQ.
                                    $set('approved_by_name', $rfq->approved_by_name);
                                    $set('approved_by_designation', $rfq->approved_by_designation);
                                }
                            })
                            ->columnSpan(2),
                    ]),

                Forms\Components\Section::make('Supplier Columns')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('supplier1_name')
                                    ->label('Supplier 1')
                                    ->options([
                                        'Starbright Home Depot INC.' => 'Starbright Home Depot INC.',
                                        'Kristan Educational Supply' => 'Kristan Educational Supply',
                                        'New Marbel Top Commercial' => 'New Marbel Top Commercial',
                                        'Peoples General Merchandise' => 'Peoples General Merchandise',
                                        'KCC Mall of Marbel' => 'KCC Mall of Marbel',
                                        'Gaisano Grand Mall of Marbel' => 'Gaisano Grand Mall of Marbel',
                                    ])
                                    ->required(),
                                Forms\Components\Select::make('supplier2_name')
                                    ->label('Supplier 2')
                                    ->options([
                                        'Starbright Home Depot INC.' => 'Starbright Home Depot INC.',
                                        'Kristan Educational Supply' => 'Kristan Educational Supply',
                                        'New Marbel Top Commercial' => 'New Marbel Top Commercial',
                                        'Peoples General Merchandise' => 'Peoples General Merchandise',
                                        'KCC Mall of Marbel' => 'KCC Mall of Marbel',
                                        'Gaisano Grand Mall of Marbel' => 'Gaisano Grand Mall of Marbel',
                                    ])
                                    ->required(),
                                Forms\Components\Select::make('supplier3_name')
                                    ->label('Supplier 3')
                                    ->options([
                                        'Starbright Home Depot INC.' => 'Starbright Home Depot INC.',
                                        'Kristan Educational Supply' => 'Kristan Educational Supply',
                                        'New Marbel Top Commercial' => 'New Marbel Top Commercial',
                                        'Peoples General Merchandise' => 'Peoples General Merchandise',
                                        'KCC Mall of Marbel' => 'KCC Mall of Marbel',
                                        'Gaisano Grand Mall of Marbel' => 'Gaisano Grand Mall of Marbel',
                                    ])
                                    ->required(),
                            ]),
                    ])
                    ->description('Enter the names of the suppliers canvassed. The supplier with the lowest total quotation is resolved as the winning supplier and displayed when creating a Purchase Order. Supplier contact details on the Purchase Order are entered manually.'),

                Forms\Components\Section::make('Items & Prices')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Grid::make(12)
                                    ->schema([
                                        Forms\Components\TextInput::make('item_number')
                                            ->label('Item')
                                            ->numeric()
                                            ->columnSpan(1)
                                            // Read-only: the number is assigned
                                            // automatically (1, 2, 3, …) and
                                            // re-calculated on add/remove.
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Qty')
                                            ->numeric()
                                            ->columnSpan(1)
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('unit')
                                            ->label('Unit')
                                            ->columnSpan(1)
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->rows(2)
                                            ->columnSpan(3)
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('supplier1_price')
                                            ->label('Supplier 1 Price')
                                            ->numeric()
                                            ->prefix('₱')
                                            ->placeholder('0.00')
                                            ->columnSpan(2)
                                            ->required(),
                                        Forms\Components\TextInput::make('supplier2_price')
                                            ->label('Supplier 2 Price')
                                            ->numeric()
                                            ->prefix('₱')
                                            ->placeholder('0.00')
                                            ->columnSpan(2)
                                            ->required(),
                                        Forms\Components\TextInput::make('supplier3_price')
                                            ->label('Supplier 3 Price')
                                            ->numeric()
                                            ->prefix('₱')
                                            ->placeholder('0.00')
                                            ->columnSpan(2)
                                            ->required(),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->maxItems(30)
                            ->addable(false)
                            ->deletable()
                            ->reorderable(false)
                            ->afterStateUpdated(function ($state, $livewire) {
                                // Keep item numbers sequential (1, 2, 3, …)
                                // whenever a row is added or removed. Keys are
                                // preserved so the relationship sync still
                                // matches existing rows.
                                $livewire->data['items'] = AbstractOfCanvassService::renumberItems(
                                    is_array($state) ? $state : []
                                );
                            })
                            ->afterStateHydrated(function ($state, $livewire) {
                                // Recalculate on load so legacy records with
                                // gaps or duplicates display consecutive
                                // numbers immediately.
                                $livewire->data['items'] = AbstractOfCanvassService::renumberItems(
                                    is_array($state) ? $state : []
                                );
                            }),
                    ]),

                Forms\Components\Section::make('Committee on Awards')
                    ->schema([
                        Forms\Components\Grid::make(5)
                            ->schema([
                                Forms\Components\Fieldset::make('Chairman')
                                    ->schema([
                                        Forms\Components\Select::make('chairman_name')
                                            ->label('Name')
                                            ->options([
                                                 'Miguel A. Peñaloza' => 'Miguel A. Peñaloza',
                                                 'Rizalyn C. Isnani-Concha' => 'Rizalyn C. Isnani-Concha',
                                                 ])
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\TextInput::make('chairman_designation')
                                            ->label('Designation')
                                            ->default('Chairman')
                                            ->disabled()
                                            ->dehydrated(),
                                    ]),
                                Forms\Components\Fieldset::make('Vice Chairman')
                                    ->schema([
                                        Forms\Components\Select::make('vice_chairman_name')
                                            ->label('Name')
                                            ->options([
                                                 'Atty. Mae P. Galong' => 'Atty. Mae P. Galong',
                                                 'Atty. Reuben P. Escarlan' => 'Atty. Reuben P. Escarlan',
                                                 ])
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\TextInput::make('vice_chairman_designation')
                                            ->label('Designation')
                                            ->default('Vice Chairman')
                                            ->disabled()
                                            ->dehydrated(),
                                    ]),
                                Forms\Components\Fieldset::make('Member 1')
                                    ->schema([
                                        Forms\Components\Select::make('member1_name')
                                            ->label('Name')
                                            ->options([
                                                 'Atty. Reuben P. Escarlan' => 'Atty. Reuben P. Escarlan',
                                                 'Atty. Mae P. Galong' => 'Atty. Mae P. Galong',
                                                 ])
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\TextInput::make('member1_designation')
                                            ->label('Designation')
                                            ->default('Member')
                                            ->disabled()
                                            ->dehydrated(),
                                    ]),
                                Forms\Components\Fieldset::make('Member 2')
                                    ->schema([
                                        Forms\Components\Select::make('member2_name')
                                            ->label('Name')
                                            ->options([
                                                 'Arnold B. Aumento' => 'Arnold B. Aumento',
                                                 'Rizalyn C. Isnani-Concha' => 'Rizalyn C. Isnani-Concha',
                                                 ])
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\TextInput::make('member2_designation')
                                            ->label('Designation')
                                            ->default('Member')
                                            ->disabled()
                                            ->dehydrated(),
                                    ]),
                                Forms\Components\Fieldset::make('Member 3')
                                    ->schema([
                                        Forms\Components\Select::make('member3_name')
                                            ->label('Name')
                                            ->options([
                                                 'Rizalyn C. Isnani-Concha' => 'Rizalyn C. Isnani-Concha',
                                                 'Haiziah B. Lauban' => 'Haiziah B. Lauban',
                                                 ])
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\TextInput::make('member3_designation')
                                            ->label('Designation')
                                            ->default('Member')
                                            ->disabled()
                                            ->dehydrated(),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Award Recommendation')
                    ->hidden()
                    ->schema([
                        Forms\Components\Select::make('winning_supplier_id')
                            ->label('Winning Supplier')
                            ->placeholder('Select the winning supplier...')
                            ->relationship('winningSupplier', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Select the supplier to award. Auto-populated from lowest total during calculation.')
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $supplier = \App\Models\Supplier::find($state);
                                    $set('recommendation', $supplier?->name ?? '');
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('recommendation')
                            ->label('Recommended Awardee (text)')
                            ->helperText('Auto-computed from winning supplier selection. Saved as text record.')
                            ->rows(2)
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull(),
                    ]),

                        Forms\Components\Fieldset::make('Approved by')
                                    ->schema([
                                        Forms\Components\TextInput::make('approved_by_name')
                                            ->label('Name')
                                            ->disabled()
                                            ->dehydrated(false),
                                        Forms\Components\TextInput::make('approved_by_designation')
                                            ->label('Designation')
                                            ->disabled()
                                            ->dehydrated(false),

                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['editingUser', 'rfq.purchaseRequest']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('abc_no')
                    ->label('ABC No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('editingDisplayName')
                    ->label('Editing')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-lock-open')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('rfq.rfq_no')
                    ->label('RFQ Source')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_of_advertisement')
                    ->label('Advertised')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_of_opening')
                    ->label('Opening')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('recommendation')
                    ->label('Award')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->label('Print ABC')
                    ->icon('heroicon-o-printer')
                    ->visible(fn (): bool => ! CurrentUser::get()?->isRegionalDirector() && ! CurrentUser::get()?->isStaff() && ! CurrentUser::get()?->isDivisionChief())
                    ->url(fn (AbstractOfCanvass $record): string => route('abc.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('abstracts_of_canvass.update') && ! CurrentUser::get()?->isDivisionChief()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('abstracts_of_canvass.delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('abstracts_of_canvass.delete')),
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
            'index' => Pages\ListAbstractsOfCanvass::route('/'),
            'create' => Pages\CreateAbstractOfCanvass::route('/create'),
            'view' => Pages\ViewAbstractOfCanvass::route('/{record}'),
            'edit' => Pages\EditAbstractOfCanvass::route('/{record}/edit'),
        ];
    }
}
