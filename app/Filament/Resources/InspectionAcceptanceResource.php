<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\InspectionAcceptanceResource\Pages;
use App\Models\Category;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseOrder;
use App\Models\Supply;
use App\Services\ProcurementBadgeService;
use App\Services\StockInService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InspectionAcceptanceResource extends Resource
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('inspection_acceptance_reports.view');
    }

    public static function canCreate(): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('inspection_acceptance_reports.create')
            && static::hasEligibleSource();
    }

    /**
     * An Inspection and Acceptance Report can only be created when at least
     * one Purchase Order exists that has not yet been converted into an IAR.
     */
    public static function hasEligibleSource(): bool
    {
        return ProcurementBadgeService::eligibleQuery(ProcurementBadgeService::STAGE_IAR)->exists();
    }

    public static function canEdit(mixed $record = null): bool
    {
        return false;
    }

    protected static ?string $model = InspectionAcceptanceReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Procurement Management';

    protected static ?int $navigationSort = 11;

    public static function getNavigationBadge(): ?string
    {
        return ProcurementBadgeService::badgeFor(ProcurementBadgeService::STAGE_IAR, CurrentUser::get());
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('INSPECTION AND ACCEPTANCE REPORT')
                    ->description('Document Control No. CHR-ROXII-ASD-FR-013')
                    ->schema([
                        Forms\Components\Select::make('po_id')
                            ->label('Source Purchase Order')
                            ->placeholder('Select PO to load supplier & items...')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->options(function () {
                                return PurchaseOrder::query()
                                    ->eligibleForIar()
                                    ->with('items')
                                    ->get()
                                    ->mapWithKeys(function ($po) {
                                        $itemCount = $po->items->count();
                                        return [$po->id => "{$po->po_no} — {$po->supplier_name} ({$itemCount} items)"];
                                    });
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                /** @var \App\Models\PurchaseOrder|null $po */
                                $po = PurchaseOrder::with(
                                    'bacResolution.abc.rfq.purchaseRequest',
                                    'abc.rfq.purchaseRequest',
                                )->find($state);
                                if (!$po) return;

                                $set('supplier_name', $po->supplier_name ?? '');
                                $set('po_no', $po->po_no ?? '');

                                // Auto-populate the Requisitioning Office /
                                // Department from the source Purchase Request
                                // (IAR → PO → BAC Resolution → ABC → RFQ → PR).
                                // The field is read-only, so this is the only
                                // place its value is ever set.
                                $set('requisitioning_office_dept',
                                    $po->bacResolution?->abc?->rfq?->purchaseRequest?->office_division
                                    ?? $po->abc?->rfq?->purchaseRequest?->office_division
                                    ?? '');

                                if ($po->items->count() > 0) {
                                    $items = $po->items->map(function ($poItem) {
                                        return [
                                            'stock_no' => $poItem->stock_no ?? '',
                                            'unit' => $poItem->unit ?? '',
                                            'description' => $poItem->description ?? '',
                                            'quantity' => $poItem->quantity ?? 1,
                                            'unit_cost' => $poItem->unit_cost ?? 0,
                                            'category_id' => $poItem->category_id ?? null,
                                        ];
                                    })->toArray();
                                    $livewire->data['items'] = $items;
                                }
                            })
                            ->columnSpan(2),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('iar_no')
                                    ->label('IAR No.')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),
                                Forms\Components\DatePicker::make('date')
                                    ->label('Date')
                                    ->required()
                                    ->columnSpan(1),
                            ]),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                // Supplier and P.O. No. are read-only mirrors of the
                                // source Purchase Order. The model hydrates both on
                                // create (hydrateFromPo) and re-sources them live on
                                // read, so they are never dehydrated here — that
                                // keeps the Purchase Order the single source of
                                // truth and stops stale form state from persisting.
                                Forms\Components\TextInput::make('supplier_name')
                                    ->label('Supplier')
                                    ->columnSpan(2)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder(InspectionAcceptanceReport::MISSING_VALUE_PLACEHOLDER),
                                Forms\Components\TextInput::make('po_no')
                                    ->label('PO No.')
                                    ->columnSpan(1)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder(InspectionAcceptanceReport::MISSING_VALUE_PLACEHOLDER),
                            ]),
                        Forms\Components\TextInput::make('requisitioning_office_dept')
                            ->label('Requisitioning Office / Department')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Grid::make(12)
                                    ->schema([
                                        Forms\Components\Select::make('supply_id')
                                            ->label('Supply')
                                            ->searchable()
                                            ->preload()
                                            ->options(fn () => Supply::query()
                                                ->orderBy('stock_no')
                                                ->get(['id', 'stock_no', 'name'])
                                                ->mapWithKeys(fn (Supply $supply) => [
                                                    $supply->id => trim("{$supply->stock_no} - {$supply->name}", ' -'),
                                                ]))
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $supply = Supply::find($state);
                                                    if ($supply) {
                                                        $set('stock_no', $supply->stock_no);
                                                        $set('unit', $supply->unit);
                                                        $set('description', $supply->name);
                                                        $set('category_id', $supply->category_id);
                                                    }
                                                }
                                            })
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('stock_no')
                                            ->label('Stock No.')
                                            ->columnSpan(2)
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
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Qty Ordered')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(0)
                                            ->columnSpan(1)
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('unit_cost')
                                            ->label('Unit Cost')
                                            ->numeric()
                                            ->prefix('₱')
                                            ->minValue(0)
                                            ->default(0)
                                            ->columnSpan(1)
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\Select::make('category_id')
                                            ->label('Category')
                                            ->relationship('category', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(2),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->maxItems(30)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),

                Forms\Components\Section::make('Inspection & Acceptance')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Fieldset::make('Inspected by')
                                    ->schema([
                                        Forms\Components\Select::make('inspector_name')
                                            ->label('Name')
                                            ->options([
                                                'ANA FE B. GALANTO' => 'ANA FE B. GALANTO',
                                                'RODEL C. LINUGAO' => 'RODEL C. LINUGAO',
                                                'CHERRIE LYN C. OLACO' => 'CHERRIE LYN C. OLACO',
                                            ])
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\Select::make('inspector_designation')
                                            ->label('Designation')
                                            ->options([
                                                'Admin. Assistant II' => 'Admin. Assistant II',
                                                'Admin. Assistant III' => 'Admin. Assistant III',
                                                'Admin. Aide I' => 'Admin. Aide I',
                                            ])
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\DatePicker::make('inspection_date')
                                            ->label('Date')
                                            ->required(),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Checkbox::make('inspection_complete')
                                                    ->label('Complete')
                                                    //->default(true)
                                                    ->live()
                                                    ->afterStateUpdated(fn ($state, callable $set) => $state ? $set('inspection_partial', false) : null),
                                                Forms\Components\Checkbox::make('inspection_partial')
                                                    ->label('Partial')
                                                    ->live()
                                                    ->afterStateUpdated(fn ($state, callable $set) => $state ? $set('inspection_complete', false) : null),
                                            ]),
                                    ]),
                                Forms\Components\Fieldset::make('Accepted by')
                                    ->schema([
                                        Forms\Components\Select::make('acceptor_name')
                                            ->label('Name')
                                            ->options([
                                                'RHODELIA J. MANDOLADO' => 'RHODELIA J. MANDOLADO',
                                                'CHERRIE LYN C. OLACO' => 'CHERRIE LYN C. OLACO',
                                                'RODEL C. LINUGAO' => 'RODEL C. LINUGAO',
                                            ])
                                            ->required()
                                            ->searchable(),
                                        Forms\Components\Select::make('acceptor_designation')
                                            ->label('Designation')
                                            ->options([
                                                'Admin. Officer IV' => 'Admin. Officer IV',
                                                'Admin. Aide VI' =>  'Admin. Aide VI',
                                                'Admin. Aide I' => 'Admin. Aide I',
                                            ])
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\DatePicker::make('acceptance_date')
                                            ->label('Date')
                                            ->required(),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Checkbox::make('acceptance_complete')
                                                    ->label('Complete')
                                                    //->default(true)
                                                    ->live()
                                                    ->afterStateUpdated(fn ($state, callable $set) => $state ? $set('acceptance_partial', false) : null),
                                                Forms\Components\Checkbox::make('acceptance_partial')
                                                    ->label('Partial')
                                                    ->live()
                                                    ->afterStateUpdated(fn ($state, callable $set) => $state ? $set('acceptance_complete', false) : null),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('items.supply')
            // The Supplier and P.O. No. columns are sourced from the source
            // Purchase Order through the model accessors, so eager-load the
            // chain to avoid N+1 queries when listing reports.
            ->with('po.bacResolution')
            // Eager-load the BAC conversion count so the "BAC Status" column
            // can mark IARs as "Converted to BAC" without N+1 queries.
            ->withCount('bacResolutions');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('iar_no')
                    ->label('IAR No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplyNos')
                    ->label('Supply')
                    ->searchable()
                    ->sortable()
                    ->hidden(),
                Tables\Columns\TextColumn::make('stockNumbers')
                    ->label('Stock No.')
                    ->searchable()
                    ->sortable()
                    ->hidden(),

                Tables\Columns\TextColumn::make('supplier_name')
                    ->label('Supplier')
                    // Sourced from the source Purchase Order through the model
                    // accessor; search both the stored snapshot and the PO.
                    ->searchable(['supplier_name', 'po.supplier_name'])
                    ->placeholder(InspectionAcceptanceReport::MISSING_VALUE_PLACEHOLDER),
                Tables\Columns\TextColumn::make('po_no')
                    ->label('PO No.')
                    ->searchable(['po_no', 'po.po_no'])
                    ->placeholder(InspectionAcceptanceReport::MISSING_VALUE_PLACEHOLDER),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state)))
                    ->color(fn (InspectionAcceptanceReport $record): string => $record->statusBadgeColor())
                    ->sortable(),
                Tables\Columns\TextColumn::make('bac_resolutions_count')
                    ->label('BAC Status')
                    ->badge()
                    ->formatStateUsing(fn (InspectionAcceptanceReport $record): string =>
                        ($record->bac_resolutions_count ?? 0) > 0 ? 'Converted to BAC' : 'Eligible')
                    ->color(fn (InspectionAcceptanceReport $record): string =>
                        ($record->bac_resolutions_count ?? 0) > 0 ? 'success' : 'gray')
                    ->icon(fn (InspectionAcceptanceReport $record): ?string =>
                        ($record->bac_resolutions_count ?? 0) > 0 ? 'heroicon-o-check-badge' : null)
                    ->description(fn (InspectionAcceptanceReport $record): ?string =>
                        ($record->bac_resolutions_count ?? 0) > 0 ? 'IAR already used' : 'Available to convert'),
                Tables\Columns\IconColumn::make('inspection_complete')
                    ->label('Insp. Complete')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('acceptance_complete')
                    ->label('Accepted')
                    ->boolean()
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
                Tables\Actions\Action::make('stockIn')
                    ->label('Stock In')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (InspectionAcceptanceReport $record): bool =>
                        CurrentUser::get()?->hasPermissionTo('inspection_acceptance_reports.stock_in')
                        && $record->status !== InspectionAcceptanceReport::STATUS_STOCKED_IN
                    )
                    ->action(function (InspectionAcceptanceReport $record) {
                        abort_unless(CurrentUser::get()?->hasPermissionTo('inspection_acceptance_reports.stock_in'), 403);
                        try {
                            $service = app(StockInService::class);
                            $result = $service->process($record);

                            Notification::make()
                                ->success()
                                ->title('Stock In Successful')
                                ->body($result['message'])
                                ->send();
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            $errors = $e->errors();
                            $firstError = collect($errors)->flatten()->first() ?? 'Validation failed.';

                            Notification::make()
                                ->danger()
                                ->title('Stock In Failed')
                                ->body($firstError)
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Stock In Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Stock In from IAR')
                    ->modalDescription('This will automatically create stock-in records for all accepted items, update inventory quantities, and change the IAR status to "Stocked In". This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, Stock In'),
                Tables\Actions\Action::make('print')
                    ->label('Print IAR')
                    ->icon('heroicon-o-printer')
                    ->visible(fn (): bool => ! CurrentUser::get()?->isRegionalDirector() && ! CurrentUser::get()?->isStaff() && ! CurrentUser::get()?->isDivisionChief())
                    ->url(fn (InspectionAcceptanceReport $record): string => route('iar.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->isAdmin()),
            ])
            ->bulkActions([

            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInspectionAcceptanceReports::route('/'),
            'create' => Pages\CreateInspectionAcceptanceReport::route('/create'),
            'view' => Pages\ViewInspectionAcceptanceReport::route('/{record}'),
        ];
    }
}
