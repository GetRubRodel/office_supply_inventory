<?php

namespace App\Filament\Widgets;

use App\Support\CurrentUser;

use App\Models\Supply;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockSuppliesWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('supplies.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Supply::whereColumn('current_stock', '<=', 'reorder_level')
                    ->orderBy('current_stock')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->numeric()
                    ->color('danger')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('reorder_level')
                    ->numeric(),
                Tables\Columns\TextColumn::make('category.name'),
            ])
            ->paginated(false);
    }
}
