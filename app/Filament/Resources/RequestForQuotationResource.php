<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\RequestForQuotationResource\Pages;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Services\ProcurementBadgeService;
use App\Support\TinFormatter;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RequestForQuotationResource extends Resource
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('request_for_quotations.view');
    }

    public static function canCreate(): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('request_for_quotations.create')
            && static::hasEligibleSource();
    }

    /**
     * An RFQ can only be created when at least one approved Purchase Request
     * exists that has not yet been converted into a Request for Quotation.
     */
    public static function hasEligibleSource(): bool
    {
        return ProcurementBadgeService::eligibleQuery(ProcurementBadgeService::STAGE_RFQ)->exists();
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        // Division Chief may not edit RFQ records.
        if ($user->isDivisionChief()) {
            return false;
        }

        return $user->hasPermissionTo('request_for_quotations.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        // Supply Officer may not delete RFQ records.
        if ($user->isSupplyOfficer()) {
            return false;
        }

        return $user->hasPermissionTo('request_for_quotations.delete');
    }

    protected static ?string $model = RequestForQuotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Procurement Management';

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        return ProcurementBadgeService::badgeFor(ProcurementBadgeService::STAGE_RFQ, CurrentUser::get());
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('REQUEST FOR QUOTATION')
                    ->description('Document Control No. CHR-ROXII-ASD-FR-007')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('rfq_no')
                                    ->label('RFQ No.')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(2),
                                Forms\Components\DatePicker::make('date')
                                    ->label('Date')
                                    ->minDate(now()->startOfDay())
                                    ->required()
                                    ->columnSpan(1),
                            ]),
                        Forms\Components\Select::make('purchase_request_id')
                            ->label('Source Purchase Request')
                            ->placeholder('Select a PR to load items from...')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->options(function () {
                                return PurchaseRequest::query()
                                    ->eligibleForRfq()
                                    ->with('items')
                                    ->get()
                                    ->mapWithKeys(function ($pr) {
                                        $itemCount = $pr->items->count();
                                        return [$pr->id => "{$pr->pr_no} ({$itemCount} items)"];
                                    });
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                /** @var \App\Models\PurchaseRequest|null $pr */
                                $pr = PurchaseRequest::with('items')->find($state);
                                if ($pr && $pr->items->count() > 0) {
                                    $items = $pr->items->map(fn($item) => [
                                        'stock_property_no' => $item->stock_property_no,
                                        'unit' => $item->unit,
                                        'item_description' => $item->item_description,
                                        'quantity' => $item->quantity,
                                        'unit_cost' => $item->unit_cost,
                                    ])->toArray();
                                    $livewire->data['items'] = $items;
                                } else {
                                    // A source PR without items must not leave stale
                                    // rows behind when the selection changes.
                                    $livewire->data['items'] = [];
                                }
                                if ($pr) {
                                    // Approval details always come from the source PR.
                                    $set('approved_by_name', $pr->approved_by_name);
                                    $set('approved_by_designation', $pr->approved_by_designation);
                                }
                            })
                            ->columnSpan(2),
                        Forms\Components\Grid::make(2)
                            ->hidden()
                            ->schema([
                                Forms\Components\TextInput::make('company_name')
                                    ->label('Supplier Name'),
                                Forms\Components\TextInput::make('contact_number')
                                    ->label('Contact Number')
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
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('tin')
                                    ->label('TIN Number')
                                    ->maxLength(15)
                                    ->formatStateUsing(fn (?string $state): ?string => TinFormatter::format($state))
                                    ->dehydrateStateUsing(fn (?string $state): ?string => $state === null ? null : preg_replace('/\D/', '', $state))
                                    ->rule(function (): Closure {
                                        return function (string $attribute, mixed $value, Closure $fail): void {
                                            $value = (string) $value;

                                            // Empty values are allowed (field is optional).
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
                            ]),
                    ]),

                Forms\Components\Section::make('Items to Quote')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Grid::make(6)
                                    ->schema([
                                        Forms\Components\TextInput::make('stock_property_no')
                                            ->label('Stock No.')
                                            ->columnSpan(1)
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('unit')
                                            ->label('Unit')
                                            ->columnSpan(1)
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->columnSpan(1)
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\Textarea::make('item_description')
                                            ->label('Description of Article/s')
                                            ->rows(2)
                                            ->columnSpan(2)
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('unit_cost')
                                            ->label('Unit Cost')
                                            ->numeric()
                                            ->prefix('₱')
                                            ->placeholder('(optional)')
                                            ->columnSpan(1)
                                            ->hidden(),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->maxItems(30)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),

                Forms\Components\Section::make('Signatories')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Fieldset::make('Canvassed by')
                                    ->schema([
                                        Forms\Components\Select::make('canvassed_by_name')
                                            ->label('Name of Canvasser')
                                            ->options([
                                                 'Rodel C. Linugao' => 'Rodel C. Linugao',
                                                 'Wesley C. Calamba' => 'Wesley C. Calamba',
                                                 'Mohammad Ali C. Usman' => 'Mohammad Ali C. Usman',
                                                 'Cherrie Lyn C. Olaco' => 'Cherrie Lyn C. Olaco',
                                                 'Tessa May S. Balane' => 'Tessa May S. Balane',
                                                 'Ana Fe B. Galanto' => 'Ana Fe B. Galanto',
                                                 'Rosie D. Noleal' => 'Rosie D. Noleal',
                                                 'Romula M. Lakim' => 'Romula M. Lakim',
                                                 'Carlo Mario A. Hofeliña' => 'Carlo Mario A. Hofeliña',
                                                 'Anna May B. Pahilagao' => 'Anna May B. Pahilagao',
                                                 'Rhodelia J. Mandolado' => 'Rhodelia J. Mandolado',
                                                 'Jonroe C. Sagra' => 'Jonroe C. Sagra',
                                                 'Glenda T. Chiva' => 'Glenda T. Chiva',
                                                 'Shaira Vanessa N. Brahim' => 'Shaira Vanessa N. Brahim',
                                                 'Sittie Rakima M. Kapampangan' => 'Sittie Rakima M. Kapampangan',
                                                 'Ralph Jude B. Delposo' => 'Ralph Jude B. Delposo',
                                                 'Baisarah M. Abdullah' => 'Baisarah M. Abdullah',
                                                 'Haizia B. Lauban' => 'Haizia B. Lauban',
                                                 'Rizalyn C. Isnani-Concha' => 'Rizalyn C. Isnani-Concha',
                                                 'Miguel A. Peñaloza' => 'Miguel A. Peñaloza',
                                                 'Arnold B. Aumento' => 'Arnold B. Aumento',
                                                 'Loreto B. Condesa' => 'Loreto B. Condesa',
                                                 'Atty. Stephanie D. Publico-Lopez' => 'Atty. Stephanie D. Publico-Lopez',
                                                 'Atty. Reuben P. Escarlan' => 'Atty. Reuben P. Escarlan',
                                                 'Atty. Mae P. Galong' => 'Atty. Mae P. Galong',
                                                 'Atty. Benedict Mananguit' => 'Atty. Benedict Mananguit',
                                                 'Atty. Keysie M. Gomez' => 'Atty. Keysie M. Gomez',
                                                 ])
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\Select::make('canvassed_by_designation')
                                            ->label('Designation')
                                            ->options([
                                                 'Administrative Aide-I' => 'Administrative Aide-I',
                                                 'Administrative Aide-II' => 'Administrative Aide-II',
                                                 'Administrative Aide-III' => 'Administrative Aide-III',
'Administrative Aide-IV' => 'Administrative Aide-IV',
'Administrative Aide-V' => 'Administrative Aide-V',
 'Administrative Aide-VI' => 'Administrative Aide-VI',
 'Administrative Officer I' => 'Administrative Officer I',
 'Administrative Officer II' => 'Administrative Officer II',
 'Administrative Officer III' => 'Administrative Officer III',
 'Administrative Officer IV' => 'Administrative Officer IV',
 'Administrative Assistant I' => 'Administrative Assistant I',
 'Administrative Assistant II' => 'Administrative Assistant II',
'Administrative Assistant III' => 'Administrative Assistant III',
'Security Officer I' => 'Security Officer I',
 'Security Officer II' => 'Security Officer II',
'Process Server' => 'Process Server',
'Special Investigator I' => 'Special Investigator I',
 'Special Investigator II' => 'Special Investigator II',
 'Special Investigator III' => 'Special Investigator III',
'Special Investigator IV' => 'Special Investigator IV',
'Information Officer I' => 'Information Officer I',
'Information Officer II' => 'Information Officer II',
'Training Specialist I' => 'Training Specialist I',
 'Training Specialist II' => 'Training Specialist II',
'Attorney I' => 'Attorney I', 'Attorney II' => 'Attorney II',
'Attorney III' => 'Attorney III',
 'Attorney IV' => 'Attorney IV',
'Attorney V' => 'Attorney V',
'OIC Director' => 'OIC Director',
'Regional Director' => 'Regional Director',


                                            ])
                                            ->searchable()
                                            ->required(),
                                    ]),
                                Forms\Components\Fieldset::make('Quoted by')
                                    ->hidden()
                                    ->schema([
                                        Forms\Components\TextInput::make('quoted_by_name')
                                            ->label('Name & Signature of Supplier'),
                                        Forms\Components\TextInput::make('quoted_by_supplier')
                                            ->label('Supplier Company'),
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
                            ]),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['editingUser', 'purchaseRequest']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rfq_no')
                    ->label('RFQ No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('editingDisplayName')
                    ->label('Editing')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-lock-open')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('purchaseRequest.pr_no')
                    ->label('PR Source')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('canvassed_by_name')
                    ->label('Canvasser')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('quoted_by_name')
                    ->label('Quoted By')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('address')
                    ->label('Address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contact_number')
                    ->label('Contact Number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tin')
                    ->label('TIN Number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approved_by_name')
                    ->label('Approved By')
                    ->searchable(),
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
                    ->label('Print RFQ')
                    ->icon('heroicon-o-printer')
                    ->visible(fn (): bool => ! CurrentUser::get()?->isRegionalDirector() && ! CurrentUser::get()?->isStaff() && ! CurrentUser::get()?->isDivisionChief())
                    ->url(fn (RequestForQuotation $record): string => route('rfq.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('request_for_quotations.update') && ! CurrentUser::get()?->isDivisionChief()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('request_for_quotations.delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('request_for_quotations.delete')),
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
            'index' => Pages\ListRequestForQuotations::route('/'),
            'create' => Pages\CreateRequestForQuotation::route('/create'),
            'view' => Pages\ViewRequestForQuotation::route('/{record}'),
            'edit' => Pages\EditRequestForQuotation::route('/{record}/edit'),
        ];
    }
}
