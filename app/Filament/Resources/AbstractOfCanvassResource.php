<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\AbstractOfCanvassResource\Pages;
use App\Models\AbstractOfCanvass;
use App\Models\RequestForQuotation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
        $convertedRfqIds = AbstractOfCanvass::query()
            ->whereNotNull('rfq_id')
            ->pluck('rfq_id');

        return RequestForQuotation::query()
            ->whereNotIn('id', $convertedRfqIds)
            ->exists();
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('abstracts_of_canvass.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('abstracts_of_canvass.delete');
    }

    protected static ?string $model = AbstractOfCanvass::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Purchase Management';

    protected static ?int $navigationSort = 8;

    public static function getNavigationBadge(): ?string
    {
        $user = CurrentUser::get();
        if (! $user || ! $user->canManageProcurement()) {
            return null;
        }

        $count = RequestForQuotation::whereNotIn('id', function ($query) {
            $query->select('rfq_id')
                ->from('abstracts_of_canvass')
                ->whereNotNull('rfq_id');
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
                                    ->columnSpan(1),
                                Forms\Components\DatePicker::make('date_of_opening')
                                    ->label('Date of Opening')
                                    ->columnSpan(1),
                            ]),
                        Forms\Components\Select::make('rfq_id')
                            ->label('Source RFQ')
                            ->placeholder('Select RFQ to load items...')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->options(function () {
                                $usedRfqIds = AbstractOfCanvass::pluck('rfq_id')->filter()->unique();

                                return RequestForQuotation::with('items')
                                    ->whereNotIn('id', $usedRfqIds)
                                    ->get()
                                    ->mapWithKeys(function ($rfq) {
                                        $itemCount = $rfq->items->count();
                                        return [$rfq->id => "{$rfq->rfq_no} ({$itemCount} items)"];
                                    });
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                /** @var \App\Models\RequestForQuotation|null $rfq */
                                $rfq = RequestForQuotation::with('items')->find($state);
                                if ($rfq && $rfq->items->count() > 0) {
                                    $items = $rfq->items->map(fn($item, $idx) => [
                                        'item_number' => $idx + 1,
                                        'unit' => $item->unit,
                                        'quantity' => $item->quantity,
                                        'description' => $item->item_description,
                                        'supplier1_price' => null,
                                        'supplier2_price' => null,
                                        'supplier3_price' => null,
                                    ])->toArray();
                                    $livewire->data['items'] = $items;
                                }
                            })
                            ->columnSpan(2),
                    ]),

                Forms\Components\Section::make('Supplier Columns')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('supplier1_name')
                                    ->label('Supplier 1')
                                    ->required(),
                                Forms\Components\TextInput::make('supplier2_name')
                                    ->label('Supplier 2')
                                    ->required(),
                                Forms\Components\TextInput::make('supplier3_name')
                                    ->label('Supplier 3')
                                    ->required(),
                            ]),
                    ])
                    ->description('Enter names exactly as listed in the Supplier module (case-insensitive) so the winning supplier\'s address, contact number, and TIN carry over to the Purchase Order.'),

                Forms\Components\Section::make('Items & Prices')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Grid::make(7)
                                    ->schema([
                                        Forms\Components\TextInput::make('item_number')
                                            ->label('Item')
                                            ->numeric()
                                            ->columnSpan(1)
                                            ->hidden(),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Qty')
                                            ->numeric()
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('unit')
                                            ->label('Unit')
                                            ->columnSpan(1),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->rows(2)
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('supplier1_price')
                                            ->label('Supplier 1 Price')
                                            ->numeric()
                                            ->prefix('₱')
                                            ->placeholder('0.00')
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('supplier2_price')
                                            ->label('Supplier 2 Price')
                                            ->numeric()
                                            ->prefix('₱')
                                            ->placeholder('0.00')
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('supplier3_price')
                                            ->label('Supplier 3 Price')
                                            ->numeric()
                                            ->prefix('₱')
                                            ->placeholder('0.00')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->defaultItems(5)
                            ->maxItems(30)
                            ->addActionLabel('Add Item'),
                    ]),

                Forms\Components\Section::make('Committee on Awards')
                    ->schema([
                        Forms\Components\Grid::make(5)
                            ->schema([
                                Forms\Components\Fieldset::make('Chairman')
                                    ->schema([
                                        Forms\Components\TextInput::make('chairman_name')
                                            ->label('Name')
                                            ->required(),
                                        Forms\Components\TextInput::make('chairman_designation')
                                            ->label('Designation')
                                            ->required(),
                                    ]),
                                Forms\Components\Fieldset::make('Vice Chairman')
                                    ->schema([
                                        Forms\Components\TextInput::make('vice_chairman_name')
                                            ->label('Name')
                                            ->required(),
                                        Forms\Components\TextInput::make('vice_chairman_designation')
                                            ->label('Designation')
                                            ->required(),
                                    ]),
                                Forms\Components\Fieldset::make('Member 1')
                                    ->schema([
                                        Forms\Components\TextInput::make('member1_name')
                                            ->label('Name')
                                            ->required(),
                                        Forms\Components\TextInput::make('member1_designation')
                                            ->label('Designation')
                                            ->required(),
                                    ]),
                                Forms\Components\Fieldset::make('Member 2')
                                    ->schema([
                                        Forms\Components\TextInput::make('member2_name')
                                            ->label('Name')
                                            ->required(),
                                        Forms\Components\TextInput::make('member2_designation')
                                            ->label('Designation')
                                            ->required(),
                                    ]),
                                Forms\Components\Fieldset::make('Member 3')
                                    ->schema([
                                        Forms\Components\TextInput::make('member3_name')
                                            ->label('Name')
                                            ->required(),
                                        Forms\Components\TextInput::make('member3_designation')
                                            ->label('Designation')
                                            ->required(),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Award Recommendation')
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

                Forms\Components\Section::make('Approved by')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('approved_by_name')
                                    ->label('Name')
                                    ->required(),
                                Forms\Components\TextInput::make('approved_by_designation')
                                    ->label('Designation')
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('editingUser');
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
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('rfq.rfq_no')
                    ->label('RFQ')
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
                    ->counts('items'),
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
                    ->url(fn (AbstractOfCanvass $record): string => route('abc.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('abstracts_of_canvass.update')),
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
