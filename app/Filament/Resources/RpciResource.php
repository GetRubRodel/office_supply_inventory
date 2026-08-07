<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\RpciResource\Pages;
use App\Models\Rpci;
use App\Models\Supply;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table as FilamentTable;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
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
                                            ->default(0)
                                            ->columnSpan(1)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $bpc = (int) ($get('balance_per_card') ?? 0);
                                                $qty = (int) ($state ?? 0);
                                                $diff = $bpc - $qty;
                                                $set('shortage_quantity', $diff);
                                                $set('shortage_value', round($diff * (float) ($get('unit_value') ?? 0), 2));
                                            }),
                                        Forms\Components\TextInput::make('shortage_quantity')
                                            ->label('S/O Qty')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('shortage_value')
                                            ->label('S/O Value')
                                            ->numeric()
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

                // ─── LOAD INVENTORY ITEMS BUTTON ───────────────────
                Forms\Components\Section::make('Load Inventory Items')
                    ->description('Click the button below to load all active supplies from the inventory as line items. This will replace any existing items.')
                    ->schema([
                        Forms\Components\Placeholder::make('load_items_info')
                            ->label('')
                            ->content('This will replace any existing items with the current active inventory list.'),
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
