<?php

namespace App\Filament\Pages;

use App\Support\CurrentUser;

use App\Models\RequisitionItem;
use App\Models\Category;
use App\Models\Supply;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class RsmiReport extends Page
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('rsmi_reports.view');
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.rsmi-report';

    protected ?string $maxContentWidth = '4xl';

    public ?array $filters = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('date_from')
                    ->label('Date From'),
                DatePicker::make('date_to')
                    ->label('Date To'),
                Select::make('category_id')
                    ->label('Item Category')
                    ->options(Category::pluck('name', 'id'))
                    ->placeholder('All Categories'),
                Select::make('supply_id')
                    ->label('Specific Item')
                    ->options(Supply::pluck('name', 'id'))
                    ->placeholder('All Items')
                    ->searchable(),
            ])
            ->statePath('filters');
    }

    public function getIssuedItems()
    {
        $query = RequisitionItem::with([
            'requisition',
            'supply',
        ])
            ->whereHas('requisition', function (Builder $q) {
                $q->whereIn('status', ['issued', 'pending_receipt', 'received']);
            });

        if ($this->filters['date_from'] ?? null) {
            $query->whereHas('requisition', fn (Builder $q) =>
                $q->whereDate('issued_by_date', '>=', $this->filters['date_from'])
            );
        }

        if ($this->filters['date_to'] ?? null) {
            $query->whereHas('requisition', fn (Builder $q) =>
                $q->whereDate('issued_by_date', '<=', $this->filters['date_to'])
            );
        }

        if ($this->filters['category_id'] ?? null) {
            $query->whereHas('supply', fn (Builder $q) =>
                $q->where('category_id', $this->filters['category_id'])
            );
        }

        if ($this->filters['supply_id'] ?? null) {
            $query->where('supply_id', $this->filters['supply_id']);
        }

        return $query
            ->orderBy('id')
            ->get();
    }

    public function getRecapitulation($items = null)
    {
        $items = $items ?? $this->getIssuedItems();

        return $items
            ->groupBy(fn ($item) => $item->supply?->stock_no ?? $item->stock_no)
            ->map(function ($group, $stockNo) {
                $first = $group->first();
                $supply = $first->supply;
                $unitCost = (float) ($first->price ?? $supply?->unit_price ?? 0);
                $totalQty = $group->sum('quantity_issued');

                return [
                    'stock_number' => $stockNo,
                    'item_description' => $supply?->name ?? $first->description,
                    'unit' => $supply?->unit ?? $first->unit,
                    'quantity' => $totalQty,
                    'unit_cost' => $unitCost,
                ];
            })
            ->values()
            ->toArray();
    }

    public function getGrandTotalQuantity()
    {
        return $this->getIssuedItems()->sum('quantity_issued');
    }

    public function getGrandTotalAmount()
    {
        return $this->getIssuedItems()->sum(function ($item) {
            $unitCost = (float) ($item->price ?? $item->supply?->unit_price ?? 0);
            return $item->quantity_issued * $unitCost;
        });
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
        $url = route('ris.rsmi', array_filter($this->filters ?? []));
        $this->dispatch('open-print-url', url: $url);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printReport')
                ->label('Print RSMI')
                ->icon('heroicon-o-printer')
                ->action('printReport'),
        ];
    }
}
