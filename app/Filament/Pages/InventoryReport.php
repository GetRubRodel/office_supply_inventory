<?php

namespace App\Filament\Pages;

use App\Support\CurrentUser;
use App\Support\ReportClock;
use App\Models\Category;

use App\Models\Supplier;
use App\Models\Supply;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table as TableStyle;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReport extends Page
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('inventory_reports.view');
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Report Management';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.inventory-report';

    public ?array $filters = [];

    /**
     * Per-request memoization of getSupplies(). The Blade view calls it three
     * times (summary count + two tables); without this each call would re-run
     * the whole query. Protected so Livewire does not serialize it between
     * requests (filters are re-applied on every request anyway).
     *
     * @var \Illuminate\Support\Collection<int, \App\Models\Supply>|null
     */
    protected ?\Illuminate\Support\Collection $suppliesMemo = null;

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
                    ->options(fn () => Cache::remember(
                        'inventory_report.filter.categories',
                        300,
                        fn () => Category::pluck('name', 'id')
                    ))
                    ->placeholder('All Categories'),
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->options(fn () => Cache::remember(
                        'inventory_report.filter.suppliers',
                        300,
                        fn () => Supplier::pluck('name', 'id')
                    ))
                    ->placeholder('All Suppliers'),
                Select::make('supply_id')
                    ->label('Item')
                    ->options(fn () => Cache::remember(
                        'inventory_report.filter.supplies',
                        300,
                        fn () => Supply::pluck('name', 'id')
                    ))
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
     *
     * The two aggregates are computed with correlated sub-selects (withSum)
     * so the whole report is a single query instead of 2N+1 queries.
     */
    public function getSupplies()
    {
        if ($this->suppliesMemo !== null) {
            return $this->suppliesMemo;
        }

        $query = Supply::with(['category', 'supplier'])
            // Total Stock In: sum of quantity_received from stock_in_records.
            ->withSum('stockInRecords as total_stock_in', 'quantity_received')
            // Total Stock Out: sum of quantity_issued from issued requisition_items.
            ->withSum(['requisitionItems as total_stock_out' => function ($q) {
                $q->where('quantity_issued', '>', 0)
                    ->whereHas('requisition', function ($rq) {
                        $rq->whereIn('status', ['issued', 'pending_receipt', 'received']);

                        if ($this->filters['date_from'] ?? null) {
                            $rq->whereDate('issued_by_date', '>=', $this->filters['date_from']);
                        }
                        if ($this->filters['date_to'] ?? null) {
                            $rq->whereDate('issued_by_date', '<=', $this->filters['date_to']);
                        }
                    });
            }], 'quantity_issued');

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

        // Normalise the aggregate attributes and derive the display-only fields.
        foreach ($supplies as $supply) {
            $supply->total_stock_in = (int) ($supply->total_stock_in ?? 0);
            $supply->total_stock_out = (int) ($supply->total_stock_out ?? 0);

            // Current Available Stock = current_stock (already reflects all deductions)
            $supply->current_available_stock = $supply->current_stock;

            // Last Updated Date = last_received_date or updated_at
            $supply->last_updated_date = $supply->last_received_date ?? $supply->updated_at;
        }

        return $this->suppliesMemo = $supplies;
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

    /**
     * Whether the current user may print or download this report.
     * Restricted to holders of inventory_reports.print (Admin, Supply
     * Officer); Division Chief and Regional Director
     * keep view-only access to the module.
     */
    public function canPrintReport(): bool
    {
        return (bool) CurrentUser::get()?->hasPermissionTo('inventory_reports.print');
    }

    public function canDownloadReport(): bool
    {
        return $this->canPrintReport();
    }

    public function printReport(): void
    {
        abort_unless($this->canPrintReport(), 403);

        $this->dispatch('print-report');
    }

    /**
     * Report title shown centered above the table in the generated document.
     */
    protected const REPORT_TITLE = 'CHR REGION XII Office Supplies Inventory';

    /**
     * Build the printable inventory report document (A4 landscape, DOCX).
     *
     * Reads the exact same data source used by the module's displayed table
     * (including any active filters) without modifying any inventory data.
     *
     * @param  \Illuminate\Support\Collection  $supplies  Supplies annotated by getSupplies()
     */
    public function buildReportDocument($supplies): PhpWord
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);

        // A4 landscape section with appropriate page margins (twips).
        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => Converter::cmToTwip(29.7),
            'pageSizeH' => Converter::cmToTwip(21.0),
            'marginTop' => Converter::cmToTwip(1.27),
            'marginBottom' => Converter::cmToTwip(1.27),
            'marginLeft' => Converter::cmToTwip(1.0),
            'marginRight' => Converter::cmToTwip(1.0),
        ]);

        // Centered report title above the table.
        $section->addText(
            self::REPORT_TITLE,
            ['bold' => true, 'size' => 16, 'color' => '000000'],
            ['alignment' => 'center', 'spaceAfter' => 80]
        );
        $section->addText(
            ReportClock::generatedOn(),
            ['size' => 9, 'italic' => true, 'color' => '444444'],
            ['alignment' => 'center', 'spaceAfter' => 200]
        );

        // Column widths (cm) sum to the usable A4 landscape width (29.7cm minus 2cm margins).
        $tableWidths = [
            Converter::cmToTwip(2.8), // Stock No.
            Converter::cmToTwip(8.5), // Description
            Converter::cmToTwip(1.7), // UoM
            Converter::cmToTwip(2.2), // Current Qty
            Converter::cmToTwip(2.8), // Unit Cost
            Converter::cmToTwip(3.1), // Total Cost
            Converter::cmToTwip(2.2), // Total Stock In
            Converter::cmToTwip(2.2), // Total Stock Out
            Converter::cmToTwip(2.2), // Available Stock
        ];

        // Clear black borders around every cell; fixed layout so nothing overflows the page.
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'layout' => TableStyle::LAYOUT_FIXED,
            'width' => (int) array_sum($tableWidths),
            'unit' => TblWidth::TWIP,
        ]);

        $cellStyle = ['valign' => 'center'];

        // Header row: repeated on every page when the table spans multiple pages.
        $headers = [
            'Stock No.',
            'Description',
            'UoM',
            'Current Qty',
            'Unit Cost',
            'Total Cost',
            'Total Stock In',
            'Total Stock Out',
            'Available Stock',
        ];

        $headerRow = $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        foreach ($headers as $index => $header) {
            $headerRow
                ->addCell($tableWidths[$index], $cellStyle + ['bgColor' => 'D9D9D9'])
                ->addText($header, ['bold' => true, 'size' => 9], ['alignment' => 'center']);
        }

        if ($supplies->isEmpty()) {
            $emptyRow = $table->addRow(null, ['cantSplit' => true]);
            $emptyRow
                ->addCell(null, $cellStyle + ['gridSpan' => count($headers)])
                ->addText('No inventory records found.', ['italic' => true], ['alignment' => 'center']);
        }

        foreach ($supplies as $supply) {
            $row = $table->addRow(null, ['cantSplit' => true]);

            $row->addCell($tableWidths[0], $cellStyle)
                ->addText((string) ($supply->stock_no ?: '—'), null, ['alignment' => 'center']);
            $row->addCell($tableWidths[1], $cellStyle)
                ->addText((string) ($supply->name ?: '—'));
            $row->addCell($tableWidths[2], $cellStyle)
                ->addText((string) ($supply->unit ?: '—'), null, ['alignment' => 'center']);
            $row->addCell($tableWidths[3], $cellStyle)
                ->addText(number_format((float) $supply->current_stock), null, ['alignment' => 'right']);
            $row->addCell($tableWidths[4], $cellStyle)
                ->addText(number_format((float) $supply->unit_cost, 2), null, ['alignment' => 'right']);
            $row->addCell($tableWidths[5], $cellStyle)
                ->addText(number_format((float) $supply->total_cost, 2), null, ['alignment' => 'right']);
            $row->addCell($tableWidths[6], $cellStyle)
                ->addText(number_format((float) $supply->total_stock_in), null, ['alignment' => 'right']);
            $row->addCell($tableWidths[7], $cellStyle)
                ->addText(number_format((float) $supply->total_stock_out), null, ['alignment' => 'right']);
            $row->addCell($tableWidths[8], $cellStyle)
                ->addText(number_format((float) $supply->current_available_stock), null, ['alignment' => 'right']);
        }

        // Page number footer for multi-page reports.
        $footer = $section->addFooter();
        $footer->addText('Page ', ['size' => 8, 'color' => '666666'], ['alignment' => 'center']);
        $footer->addField('PAGE', [], [], '1');

        return $phpWord;
    }

    /**
     * Download the inventory report as an A4-landscape DOCX document.
     *
     * Uses only the data the authenticated user is authorized to see (the same
     * permission that guards the page itself) and never modifies inventory data.
     */
    public function downloadReport(): StreamedResponse
    {
        abort_unless($this->canDownloadReport(), 403);

        $supplies = $this->getSupplies();
        $phpWord = $this->buildReportDocument($supplies);
        $filename = 'CHR-12-Office-Supplies-Inventory-' . ReportClock::format('F d, Y h:i A') . '.docx';

        return response()->streamDownload(function () use ($phpWord) {
            IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printReport')
                ->label('Print Report')
                ->icon('heroicon-o-printer')
                ->visible(fn (): bool => $this->canPrintReport())
                ->action('printReport'),
            Action::make('downloadReport')
                ->label('Download Report')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => $this->canDownloadReport())
                ->action('downloadReport'),
        ];
    }
}
