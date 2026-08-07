<?php

namespace App\Filament\Pages;

use App\Support\CurrentUser;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\Supply;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class InventoryReport extends Page
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('inventory_reports.view');
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.inventory-report';

    public ?array $filters = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('category_id')
                    ->label('Category')
                    ->options(Category::pluck('name', 'id'))
                    ->placeholder('All Categories'),
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->options(Supplier::pluck('name', 'id'))
                    ->placeholder('All Suppliers'),
                Select::make('supply_id')
                    ->label('Item')
                    ->options(Supply::pluck('name', 'id'))
                    ->placeholder('All Items')
                    ->searchable(),
                DatePicker::make('date_from')
                    ->label('Date From'),
                DatePicker::make('date_to')
                    ->label('Date To'),
            ])
            ->statePath('filters');
    }

    /**
     * Get supplies with computed per-item aggregates for the inventory report.
     * Returns a collection where each Supply has:
     *   - total_stock_in  (sum from stock_in_records)
     *   - total_stock_out (sum from issued requisition_items)
     *   - current_available_stock (= current_stock, which already reflects deductions)
     */
    public function getSupplies()
    {
        $query = Supply::with(['category', 'supplier']);

        if ($this->filters['category_id'] ?? null) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if ($this->filters['supplier_id'] ?? null) {
            $query->where('supplier_id', $this->filters['supplier_id']);
        }

        if ($this->filters['supply_id'] ?? null) {
            $query->where('id', $this->filters['supply_id']);
        }

        $supplies = $query->orderBy('name')->get();

        // Annotate each supply with total_stock_in and total_stock_out
        foreach ($supplies as $supply) {
            // Total Stock In: sum of quantity_received from stock_in_records
            $supply->total_stock_in = $supply->stockInRecords()->sum('quantity_received');

            // Total Stock Out: sum of quantity_issued from issued requisition_items
            $stockOutQuery = \App\Models\RequisitionItem::where('supply_id', $supply->id)
                ->whereHas('requisition', fn ($q) => $q->whereIn('status', ['issued', 'pending_receipt', 'received']))
                ->where('quantity_issued', '>', 0);

            if ($this->filters['date_from'] ?? null) {
                $stockOutQuery->whereHas('requisition', fn ($q) => $q->whereDate('issued_by_date', '>=', $this->filters['date_from']));
            }
            if ($this->filters['date_to'] ?? null) {
                $stockOutQuery->whereHas('requisition', fn ($q) => $q->whereDate('issued_by_date', '<=', $this->filters['date_to']));
            }

            $supply->total_stock_out = $stockOutQuery->sum('quantity_issued');

            // Current Available Stock = current_stock (already reflects all deductions)
            $supply->current_available_stock = $supply->current_stock;

            // Last Updated Date = last_received_date or updated_at
            $supply->last_updated_date = $supply->last_received_date ?? $supply->updated_at;
        }

        return $supplies;
    }

    public function getTotalCurrentStock()
    {
        $query = Supply::query();

        if ($this->filters['category_id'] ?? null) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if ($this->filters['supplier_id'] ?? null) {
            $query->where('supplier_id', $this->filters['supplier_id']);
        }

        if ($this->filters['supply_id'] ?? null) {
            $query->where('id', $this->filters['supply_id']);
        }

        return $query->sum('current_stock');
    }

    public function getTotalInventoryValue()
    {
        $query = Supply::query();

        if ($this->filters['category_id'] ?? null) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if ($this->filters['supplier_id'] ?? null) {
            $query->where('supplier_id', $this->filters['supplier_id']);
        }

        if ($this->filters['supply_id'] ?? null) {
            $query->where('id', $this->filters['supply_id']);
        }

        return $query->sum(DB::raw('current_stock * unit_cost'));
    }

    public function getTotalStockOut()
    {
        $query = \App\Models\RequisitionItem::whereHas('requisition', function ($q) {
            $q->whereIn('status', ['issued', 'pending_receipt', 'received']);
        });

        if ($this->filters['supply_id'] ?? null) {
            $query->where('supply_id', $this->filters['supply_id']);
        }

        if ($this->filters['category_id'] ?? null) {
            $query->whereHas('supply', fn ($q) => $q->where('category_id', $this->filters['category_id']));
        }

        if ($this->filters['supplier_id'] ?? null) {
            $query->whereHas('supply', fn ($q) => $q->where('supplier_id', $this->filters['supplier_id']));
        }

        if ($this->filters['date_from'] ?? null) {
            $query->whereHas('requisition', fn ($q) => $q->whereDate('issued_by_date', '>=', $this->filters['date_from']));
        }

        if ($this->filters['date_to'] ?? null) {
            $query->whereHas('requisition', fn ($q) => $q->whereDate('issued_by_date', '<=', $this->filters['date_to']));
        }

        return $query->sum('quantity_issued');
    }

    public function getTotalStockIn()
    {
        $query = \App\Models\StockInRecord::query();

        if ($this->filters['supply_id'] ?? null) {
            $query->where('supply_id', $this->filters['supply_id']);
        }

        if ($this->filters['category_id'] ?? null) {
            $query->whereHas('supply', fn ($q) => $q->where('category_id', $this->filters['category_id']));
        }

        if ($this->filters['supplier_id'] ?? null) {
            $query->whereHas('supply', fn ($q) => $q->where('supplier_id', $this->filters['supplier_id']));
        }

        if ($this->filters['date_from'] ?? null) {
            $query->whereDate('date_received', '>=', $this->filters['date_from']);
        }

        if ($this->filters['date_to'] ?? null) {
            $query->whereDate('date_received', '<=', $this->filters['date_to']);
        }

        return $query->sum('quantity_received');
    }

    public function getIssuedItems()
    {
        $query = \App\Models\RequisitionItem::with(['supply.category', 'requisition'])
            ->whereHas('requisition', function ($q) {
                $q->whereIn('status', ['issued', 'pending_receipt', 'received']);
            })
            ->where('quantity_issued', '>', 0);

        if ($this->filters['supply_id'] ?? null) {
            $query->where('supply_id', $this->filters['supply_id']);
        }

        if ($this->filters['category_id'] ?? null) {
            $query->whereHas('supply', fn ($q) => $q->where('category_id', $this->filters['category_id']));
        }

        if ($this->filters['supplier_id'] ?? null) {
            $query->whereHas('supply', fn ($q) => $q->where('supplier_id', $this->filters['supplier_id']));
        }

        if ($this->filters['date_from'] ?? null) {
            $query->whereHas('requisition', fn ($q) => $q->whereDate('issued_by_date', '>=', $this->filters['date_from']));
        }

        if ($this->filters['date_to'] ?? null) {
            $query->whereHas('requisition', fn ($q) => $q->whereDate('issued_by_date', '<=', $this->filters['date_to']));
        }

        return $query->orderBy('id', 'desc')->get();
    }

    public function applyFilters(): void
    {
        $this->dispatch('$refresh');
    }

    public function resetFilters(): void
    {
        $this->form->fill();
        $this->dispatch('$refresh');
    }

    public function printReport(): void
    {
        $this->dispatch('print-report');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printReport')
                ->label('Print Report')
                ->icon('heroicon-o-printer')
                ->action('printReport'),
        ];
    }
}
