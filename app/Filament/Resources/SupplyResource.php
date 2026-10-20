<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\SupplyResource\Pages;
use App\Models\Supply;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class SupplyResource extends Resource
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('supplies.view');
    }

    public static function canCreate(): bool
    {
        // Any role granted the "supplies.create" permission (Administrator,
        // Supply Officer) may add supplies. Supplies are
        // also created through the IAR stock-in workflow; the dedicated
        // Create Supply page routes initial entries through the same
        // moving-average-cost pipeline.
        return CurrentUser::get()?->hasPermissionTo('supplies.create') ?? false;
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        // Division Chief may not edit Supply records.
        if ($user->isDivisionChief()) {
            return false;
        }

        return $user->hasPermissionTo('supplies.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('supplies.delete');
    }

    protected static ?string $model = Supply::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventory Management';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::whereColumn('current_stock', '<=', 'reorder_level')
            ->where('status', 'active')
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Supply Information')
                    ->schema([
                        Forms\Components\TextInput::make('stock_no')
                            ->label('Stock Number')
                            ->placeholder('Leave blank to auto-generate')
                            ->maxLength(50)
                            ->disabled(fn (string $operation) => $operation === 'edit')
                            ->dehydrated()
                            ->columnSpan(1)
                            ->hidden(),
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('name')
                            ->label('Item Description')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->hidden(),
                        Forms\Components\TextInput::make('unit')
                            ->label('Unit of Measure')
                            ->placeholder('e.g., pcs, box, ream')
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('reference_number')
                            ->label('Reference No.')
                            ->placeholder('e.g., PO-2026-0001')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),
                    ])->columns(2),
                Forms\Components\Section::make('Stock Settings')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'discontinued' => 'Discontinued',
                            ])
                            ->default('active')
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('reorder_level')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('current_stock')
                            ->label('Current Quantity')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Unit Cost (MAC)')
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Moving Average Cost — auto-calculated')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('total_cost')
                            ->label('Total Cost')
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Quantity × Unit Cost')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Selling or standard price reference')
                            ->columnSpan(1),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stock_no')
                    ->label('Stock No.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Item Description')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('category.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('unit')
                    ->label('UoM')
                    ->toggleable(),
                TextColumn::make('current_stock')
                    ->label('Qty')
                    ->numeric()
                    ->sortable()
                    ->color(fn (Supply $record): ?string => $record->isLowStock() ? 'danger' : null)
                    ->weight(fn (Supply $record): ?string => $record->isLowStock() ? 'bold' : null),
                TextColumn::make('unit_cost')
                    ->label('Unit Cost (MAC)')
                    ->money('PHP')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->money('PHP')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('PHP')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reorder_level')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'discontinued' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('stock_no')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'discontinued' => 'Discontinued',
                    ]),
                Tables\Filters\Filter::make('low_stock')
                    ->query(fn (Builder $query) => $query->whereColumn('current_stock', '<=', 'reorder_level'))
                    ->label('Low Stock Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => CurrentUser::get()?->hasPermissionTo('supplies.update') && ! CurrentUser::get()?->isDivisionChief()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => CurrentUser::get()?->hasPermissionTo('supplies.delete')),
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
            'index' => Pages\ListSupplies::route('/'),
            'create' => Pages\CreateSupply::route('/create'),
            'view' => Pages\ViewSupply::route('/{record}'),
            'edit' => Pages\EditSupply::route('/{record}/edit'),
        ];
    }
}
