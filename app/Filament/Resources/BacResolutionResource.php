<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\BacResolutionResource\Pages;
use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Services\ProcurementBadgeService;
use App\Support\BacResolutionText;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BacResolutionResource extends Resource
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('bac_resolutions.view');
    }

    public static function canCreate(): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('bac_resolutions.create')
            && static::hasEligibleSource();
    }

    /**
     * A BAC Resolution can only be created when at least one Abstract of
     * Bids and Canvass (ABC) exists that has not yet been converted into a
     * BAC Resolution.
     */
    public static function hasEligibleSource(): bool
    {
        return ProcurementBadgeService::eligibleQuery(ProcurementBadgeService::STAGE_BAC)->exists();
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        // Division Chief may not edit BAC Resolution records.
        if ($user->isDivisionChief()) {
            return false;
        }

        return $user->hasPermissionTo('bac_resolutions.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        // Supply Officer may not delete BAC Resolution records.
        if ($user->isSupplyOfficer()) {
            return false;
        }

        return $user->hasPermissionTo('bac_resolutions.delete');
    }

    protected static ?string $model = BacResolution::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Procurement Management';

    protected static ?int $navigationSort = 9;

    public static function getNavigationBadge(): ?string
    {
        return ProcurementBadgeService::badgeFor(ProcurementBadgeService::STAGE_BAC, CurrentUser::get());
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * Divisions offered by the first blank, keyed by value so the Select
     * stores the division name itself (and not the array index).
     *
     * @return array<string, string>
     */
    public static function divisionOptions(): array
    {
        return array_combine(BacResolution::DIVISIONS, BacResolution::DIVISIONS);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Source Abstract of Bids and Canvass (ABC)')
                    ->description('A BAC Resolution is created from an existing ABC. Only ABCs that have not yet been converted into a BAC Resolution can be selected — the details below are loaded automatically from the selected ABC.')
                    ->schema([
                        Forms\Components\Select::make('abc_id')
                            ->label('Source ABC')
                            ->placeholder('Select an eligible ABC to load its details...')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->searchable()
                            ->live()
                            ->options(function (?BacResolution $record): array {
                                return AbstractOfCanvass::query()
                                    ->with('winningSupplier')
                                    ->withCount(['items', 'bacResolutions'])
                                    ->where(function (Builder $query) use ($record) {
                                        $query->eligibleForBacConversion();

                                        // Keep the already-linked ABC selectable so the
                                        // label resolves on edit/view pages.
                                        if ($record?->abc_id) {
                                            $query->orWhere('id', $record->abc_id);
                                        }
                                    })
                                    ->orderByDesc('created_at')
                                    ->get()
                                    ->mapWithKeys(function (AbstractOfCanvass $abc) {
                                        $converted = $abc->bac_resolutions_count > 0 ? ' — Converted' : '';

                                        return [$abc->id => "{$abc->abc_no} — {$abc->winningSupplierName()} ({$abc->items_count} items){$converted}"];
                                    })
                                    ->all();
                            })
                            ->validationMessages([
                                'required' => 'Please select the Abstract of Bids and Canvass (ABC) to convert.',
                            ])
                            ->afterStateUpdated(function ($state, callable $set) {
                                /** @var \App\Models\AbstractOfCanvass|null $abc */
                                $abc = AbstractOfCanvass::find($state);
                                if ($abc) {
                                    // BAC member details always come from the
                                    // source ABC's "Committee on Awards".
                                    $set('chairperson_name', $abc->chairman_name);
                                    $set('chairperson_designation', $abc->chairman_designation);
                                    $set('vice_chairperson_name', $abc->vice_chairman_name);
                                    $set('vice_chairperson_designation', $abc->vice_chairman_designation);
                                    $set('member1_name', $abc->member1_name);
                                    $set('member1_designation', $abc->member1_designation);
                                    $set('member2_name', $abc->member2_name);
                                    $set('member2_designation', $abc->member2_designation);
                                    $set('member3_name', $abc->member3_name);
                                    $set('member3_designation', $abc->member3_designation);
                                    // Approval details always come from the
                                    // source ABC's "Approved by" section.
                                    $set('approved_by_name', $abc->approved_by_name);
                                    $set('approved_by_designation', $abc->approved_by_designation);
                                    // Winning supplier always comes from the
                                    // source ABC's lowest valid total bid.
                                    $set('supplier_name', $abc->winningSupplierName());
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('abc_details_preview')
                            ->label('ABC Details')
                            ->content(fn (Get $get) => view('bac.abc-preview', ['abcId' => $get('abc_id')]))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Winning Supplier & Items')
                    ->description('Automatically retrieved from the source ABC — the supplier with the lowest valid total bid. Unit costs, total costs and the total amount all come from that same winning supplier.')
                    ->schema([
                        Forms\Components\TextInput::make('supplier_name')
                            ->label('Winning Supplier')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Placeholder::make('bac_items_preview')
                            ->label('Items & Total Amount')
                            ->content(fn (Get $get) => view('bac.items-preview', ['abcId' => $get('abc_id')]))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('BIDS AND AWARDS COMMITTEE (BAC)')
                    ->description('Resolution')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('resolution_no')
                                    ->label('BAC RESO. No.')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(2),
                                Forms\Components\DatePicker::make('date')
                                    ->label('Date')
                                    ->minDate(now()->startOfDay())
                                    ->required()
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('place')
                                    ->label('Place')
                                    ->default('Koronadal City, Philippines')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),
                            ]),
                    ]),

                Forms\Components\Section::make('Resolution')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('division')
                                    ->options(self::divisionOptions())
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->native(false)
                                    // Blank 2 and Blank 4 both retrieve this value.
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $resolved = BacResolutionText::resolve(
                                            BacResolutionText::DIVISION_TOKEN,
                                            $state,
                                            null,
                                        );

                                        $set('division_blank_2', $resolved);
                                        $set('division_blank_4', $resolved);
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('division_blank_2')
                                    ->label('2nd Blank — Division')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->default(fn (Get $get): string => BacResolutionText::resolve(
                                        BacResolutionText::DIVISION_TOKEN,
                                        $get('division'),
                                    ))
                                    ->extraInputAttributes([
                                        'class' => 'bg-gray-50 text-gray-600',
                                        'readonly' => 'readonly',
                                        'tabindex' => '-1',
                                    ])
                                    ->hidden()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('budget_amount')
                                    ->label('Budget Amount')
                                    ->placeholder('Ex: Amount in words (P0,000)')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(255)
                                    // Blank 5 retrieves this value.
                                    ->afterStateUpdated(fn ($state, callable $set) => $set(
                                        'budget_blank_5',
                                        BacResolutionText::resolve(BacResolutionText::BUDGET_TOKEN, null, $state),
                                    ))
                                    ->columnSpan(1
                                    ),

                                Forms\Components\TextInput::make('division_blank_4')
                                    ->label('4th Blank — Division')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->default(fn (Get $get): string => BacResolutionText::resolve(
                                        BacResolutionText::DIVISION_TOKEN,
                                        $get('division'),
                                    ))
                                    ->extraInputAttributes([
                                        'class' => 'bg-gray-50 text-gray-600',
                                        'readonly' => 'readonly',
                                        'tabindex' => '-1',
                                    ])
                                    ->hidden()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('budget_blank_5')
                                    ->label('5th Blank — Budget Amount')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->default(fn (Get $get): string => BacResolutionText::resolve(
                                        BacResolutionText::BUDGET_TOKEN,
                                        null,
                                        $get('budget_amount'),
                                    ))
                                    ->extraInputAttributes([
                                        'class' => 'bg-gray-50 text-gray-600',
                                        'readonly' => 'readonly',
                                        'tabindex' => '-1',
                                    ])
                                    ->hidden()
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Placeholder::make('resolution_preview')
                            ->label('Resolution Text')
                            ->content(fn (Get $get) => view('bac.resolution-preview', [
                                'blocks' => BacResolutionText::blocks(
                                    $get('division'),
                                    $get('budget_amount'),
                                    $get('date'),
                                ),
                            ]))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('BAC Members')
                    ->description('Automatically retrieved from the source ABC — Committee on Awards.')
                    ->schema([
                        Forms\Components\Grid::make(5)
                            ->schema([
                                Forms\Components\Fieldset::make('Chairperson')
                                    ->schema([
                                        Forms\Components\TextInput::make('chairperson_name')
                                            ->label('Name')
                                            ->disabled()
                                            ->dehydrated(false),
                                        Forms\Components\TextInput::make('chairperson_designation')
                                            ->label('Designation')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),
                                Forms\Components\Fieldset::make('Vice-Chairperson')
                                    ->schema([
                                        Forms\Components\TextInput::make('vice_chairperson_name')
                                            ->label('Name')
                                            ->disabled()
                                            ->dehydrated(false),
                                        Forms\Components\TextInput::make('vice_chairperson_designation')
                                            ->label('Designation')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),
                                Forms\Components\Fieldset::make('Member 1')
                                    ->schema([
                                        Forms\Components\TextInput::make('member1_name')
                                            ->label('Name')
                                            ->disabled()
                                            ->dehydrated(false),
                                        Forms\Components\TextInput::make('member1_designation')
                                            ->label('Designation')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),
                                Forms\Components\Fieldset::make('Member 2')
                                    ->schema([
                                        Forms\Components\TextInput::make('member2_name')
                                            ->label('Name')
                                            ->disabled()
                                            ->dehydrated(false),
                                        Forms\Components\TextInput::make('member2_designation')
                                            ->label('Designation')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),
                                Forms\Components\Fieldset::make('Member 3')
                                    ->schema([
                                        Forms\Components\TextInput::make('member3_name')
                                            ->label('Name')
                                            ->disabled()
                                            ->dehydrated(false),
                                        Forms\Components\TextInput::make('member3_designation')
                                            ->label('Designation')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Approved by')
                    ->description('Automatically retrieved from the source ABC — Approved by.')
                    ->schema([
                        Forms\Components\Grid::make(2)
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
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['editingUser', 'abc']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resolution_no')
                    ->label('BAC RESO. No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('abc.abc_no')
                    ->label('ABC No'),
                Tables\Columns\TextColumn::make('division')
                    ->label('Division')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('budget_amount')
                    ->label('Budget Amount')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('editingDisplayName')
                    ->label('Editing')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-lock-open')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->limit(60)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('date')
                    ->date('F j, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('place')
                    ->label('Place'),
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
                    ->label('Print Resolution')
                    ->icon('heroicon-o-printer')
                    ->visible(fn (): bool => ! CurrentUser::get()?->isRegionalDirector() && ! CurrentUser::get()?->isStaff() && ! CurrentUser::get()?->isDivisionChief())
                    ->url(fn (BacResolution $record): string => route('bac.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('bac_resolutions.update') && ! CurrentUser::get()?->isDivisionChief()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('bac_resolutions.delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('bac_resolutions.delete')),
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
            'index' => Pages\ListBacResolutions::route('/'),
            'create' => Pages\CreateBacResolution::route('/create'),
            'view' => Pages\ViewBacResolution::route('/{record}'),
            'edit' => Pages\EditBacResolution::route('/{record}/edit'),
        ];
    }
}
