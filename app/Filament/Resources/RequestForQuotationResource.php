<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\RequestForQuotationResource\Pages;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Support\TinFormatter;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
        $convertedPrIds = RequestForQuotation::query()
            ->whereNotNull('purchase_request_id')
            ->pluck('purchase_request_id');

        return PurchaseRequest::query()
            ->where('status', PurchaseRequest::STATUS_APPROVED)
            ->whereNotIn('id', $convertedPrIds)
            ->exists();
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('request_for_quotations.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('request_for_quotations.delete');
    }

    protected static ?string $model = RequestForQuotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Purchase Management';

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        $user = CurrentUser::get();
        if (! $user || ! $user->canManageProcurement()) {
            return null;
        }

        $count = PurchaseRequest::where('status', PurchaseRequest::STATUS_APPROVED)
            ->whereNotIn('id', function ($query) {
                $query->select('purchase_request_id')
                    ->from('request_for_quotations')
                    ->whereNotNull('purchase_request_id');
            })
            ->count();

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
                                    ->default(now())
                                    ->required()
                                    ->columnSpan(1),
                            ]),
                        Forms\Components\Select::make('purchase_request_id')
                            ->label('Source Purchase Request')
                            ->placeholder('Select a PR to load items from...')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->options(function () {
                                $usedPrIds = RequestForQuotation::pluck('purchase_request_id')->filter()->unique();

                                return PurchaseRequest::with('items')
                                    ->where('status', PurchaseRequest::STATUS_APPROVED)
                                    ->whereNotIn('id', $usedPrIds)
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
                                }
                            })
                            ->columnSpan(2),
                        Forms\Components\Grid::make(2)
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
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('unit')
                                            ->label('Unit')
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->columnSpan(1),
                                        Forms\Components\Textarea::make('item_description')
                                            ->label('Description of Article/s')
                                            ->rows(2)
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('unit_cost')
                                            ->label('Unit Cost')
                                            ->numeric()
                                            ->prefix('₱')
                                            ->placeholder('(optional)')
                                            ->columnSpan(1)
                                            ->hidden(),
                                    ]),
                            ])
                            ->defaultItems(5)
                            ->maxItems(25)
                            ->addActionLabel('Add Item'),
                    ]),

                Forms\Components\Section::make('Signatories')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Fieldset::make('Canvassed by')
                                    ->schema([
                                        Forms\Components\TextInput::make('canvassed_by_name')
                                            ->label('Name of Canvasser')
                                            ->required(),
                                        Forms\Components\TextInput::make('canvassed_by_designation')
                                            ->label('Designation')
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
                                            ->default(function () {
                                                $rd = \App\Models\User::where('role', 'regional_director')->first();
                                                return $rd?->name ?? '';
                                            }),
                                        Forms\Components\TextInput::make('approved_by_designation')
                                            ->label('Designation')
                                            ->default(function () {
                                                $rd = \App\Models\User::where('role', 'regional_director')->first();
                                                return $rd ? \App\Models\User::getRoleLabel($rd->role) : '';
                                            }),
                                    ]),
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
                Tables\Columns\TextColumn::make('rfq_no')
                    ->label('RFQ No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('editingDisplayName')
                    ->label('Editing')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-lock-open')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseRequest.pr_no')
                    ->label('Source PR')
                    ->searchable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items'),
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
                    ->searchable()
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
                    ->label('Print RFQ')
                    ->icon('heroicon-o-printer')
                    ->url(fn (RequestForQuotation $record): string => route('rfq.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('request_for_quotations.update')),
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
