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
use Illuminate\Support\Facades\Response;

class SupplyResource extends Resource
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('supplies.view');
    }

    public static function canCreate(): bool
    {
        // Supplies should only be added via the IAR stock-in process
        return false;
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

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
                    ->visible(fn () => CurrentUser::get()?->hasPermissionTo('supplies.update')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => CurrentUser::get()?->hasPermissionTo('supplies.delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => CurrentUser::get()?->hasPermissionTo('supplies.delete')),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportCsv')
                    ->label('Export to CSV')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Export Supplies to CSV')
                    ->modalDescription('Download the supplies inventory as a CSV file.')
                    ->modalSubmitActionLabel('Download CSV')
                    ->visible(fn () => CurrentUser::get()?->hasPermissionTo('supplies.view'))
                    ->action(fn () => static::exportCsv()),
                Tables\Actions\Action::make('exportPdf')
                    ->label('Export to PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Export Supplies to PDF')
                    ->modalDescription('Generate a printable A4 PDF report of the current supplies inventory.')
                    ->modalSubmitActionLabel('Generate PDF')
                    ->visible(fn () => CurrentUser::get()?->hasPermissionTo('supplies.view'))
                    ->action(fn () => static::exportPdf()),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Export supplies to a CSV file.
     */
    public static function exportCsv()
    {
        if (! CurrentUser::get()?->hasPermissionTo('supplies.view')) {
            abort(403);
        }

        $supplies = Supply::with('category')
            ->where('status', 'active')
            ->orderBy('stock_no')
            ->get();

        $filename = 'supplies_inventory_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($supplies) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'Stock Number',
                'Item Description',
                'Category',
                'Unit of Measure',
                'Available Quantity',
                'Unit Cost',
                'Total Cost',
            ]);

            foreach ($supplies as $supply) {
                fputcsv($handle, [
                    $supply->stock_no ?? '',
                    $supply->name ?? '',
                    $supply->category?->name ?? '',
                    $supply->unit ?? '',
                    $supply->current_stock,
                    $supply->unit_cost ?? 0,
                    $supply->total_cost ?? 0,
                ]);
            }

            // Total row
            fputcsv($handle, [
                'TOTAL',
                '',
                '',
                '',
                $supplies->sum('current_stock'),
                '',
                $supplies->sum('total_cost'),
            ]);

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export supplies to a printable HTML page formatted for A4 paper.
     * Users can save as PDF via the browser's Print > Save as PDF feature.
     */
    public static function exportPdf()
    {
        if (! CurrentUser::get()?->hasPermissionTo('supplies.view')) {
            abort(403);
        }

        $supplies = Supply::with('category')
            ->where('status', 'active')
            ->orderBy('stock_no')
            ->get();

        $totalValue = $supplies->sum('total_cost');
        $generatedAt = now()->format('F d, Y h:i A');

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Supplies Inventory Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            color: #000;
            line-height: 1.3;
        }
        .report-header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }
        .report-header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .report-header h2 {
            font-size: 11pt;
            font-weight: normal;
            color: #333;
        }
        .report-header .meta {
            font-size: 7.5pt;
            color: #666;
            margin-top: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        th {
            background-color: #e5e7eb;
            border: 1px solid #000;
            padding: 4px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 7.5pt;
        }
        td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 7.5pt;
            vertical-align: top;
        }
        tr:nth-child(even) { background-color: #f9fafb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td {
            font-weight: bold;
            background-color: #e5e7eb;
            border-top: 2px solid #000;
        }
        .footer {
            margin-top: 12px;
            font-size: 7pt;
            color: #999;
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 4px;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>CHR-Office Supply Inventory Management System</h1>
        <h2>Supplies Inventory Report</h2>
        <div class="meta">Generated: ' . e($generatedAt) . ' | Total Active Items: ' . $supplies->count() . '</div>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:9%;">Stock No.</th>
                <th style="width:27%;">Item Description</th>
                <th style="width:14%;">Category</th>
                <th style="width:9%;">UoM</th>
                <th style="width:10%;" class="text-right">Available Qty</th>
                <th style="width:13%;" class="text-right">Unit Cost</th>
                <th style="width:13%;" class="text-right">Total Cost</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($supplies as $supply) {
            $html .= '<tr>
                <td class="text-center">' . e($supply->stock_no ?? '-') . '</td>
                <td>' . e($supply->name ?? '-') . '</td>
                <td>' . e($supply->category?->name ?? '-') . '</td>
                <td class="text-center">' . e($supply->unit ?? '-') . '</td>
                <td class="text-right">' . number_format($supply->current_stock) . '</td>
                <td class="text-right">₱' . number_format($supply->unit_cost, 2) . '</td>
                <td class="text-right">₱' . number_format($supply->total_cost, 2) . '</td>
            </tr>';
        }

        $html .= '<tr class="total-row">
            <td colspan="4">TOTAL</td>
            <td class="text-right">' . number_format($supplies->sum('current_stock')) . '</td>
            <td></td>
            <td class="text-right">₱' . number_format($totalValue, 2) . '</td>
        </tr>';

        $html .= '</tbody>
    </table>
    <div class="footer">CHR-Office Supply Inventory Management System &mdash; Supplies Inventory Report</div>
    <div class="no-print" style="margin-top:10px;text-align:center;font-size:8pt;color:#666;">
        Press <strong>Ctrl+P</strong> to print or save as PDF.
    </div>
</body>
</html>';

        return Response::make($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="supplies_inventory_report_' . now()->format('Y-m-d') . '.html"',
        ]);
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
