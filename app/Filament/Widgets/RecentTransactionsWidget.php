<?php

namespace App\Filament\Widgets;

use App\Support\CurrentUser;

use App\Models\RequisitionItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('supplies.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): \Illuminate\Database\Eloquent\Builder {
                $user = CurrentUser::get();

                $query = RequisitionItem::with(['supply', 'requisition'])
                    ->whereHas('requisition', function ($q) use ($user) {
                        $q->whereIn('status', ['issued', 'pending_receipt', 'received']);

                        // Staff (own-records-only) see only their own RIS transactions.
                        if ($user?->isOwnRecordsOnly()) {
                            $q->where('user_id', $user->id);
                        }
                    })
                    ->where('quantity_issued', '>', 0)
                    ->latest('id')
                    ->limit(10);

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('requisition.ris_no')
                    ->label('RIS No.'),
                Tables\Columns\TextColumn::make('supply.name')
                    ->label('Item')
                    ->getStateUsing(fn (RequisitionItem $record): string =>
                        $record->supply?->name ?? $record->description ?? 'N/A'
                    ),
                Tables\Columns\TextColumn::make('quantity_issued')
                    ->numeric()
                    ->label('Qty Issued'),
                Tables\Columns\TextColumn::make('requisition.issued_by_date')
                    ->label('Date')
                    ->date(),
            ])
            ->paginated(false);
    }
}
