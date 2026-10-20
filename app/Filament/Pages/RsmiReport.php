<?php

namespace App\Filament\Pages;

use App\Support\CurrentUser;

use App\Services\RsmiReportService;
use App\Support\ReportClock;
use App\Models\Category;
use App\Models\Supply;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table as TableStyle;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RsmiReport extends Page
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('rsmi_reports.view');
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?string $navigationGroup = 'Report Management';

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
        return $this->service()->items($this->filters ?? []);
    }

    public function getRecapitulation($items = null)
    {
        $items ??= $this->getIssuedItems();

        return $this->service()->recapitulation($items);
    }

    public function getGrandTotalQuantity()
    {
        return $this->service()->grandTotalQuantity($this->getIssuedItems());
    }

    public function getGrandTotalAmount()
    {
        return $this->service()->grandTotalAmount($this->getIssuedItems());
    }

    private function service(): RsmiReportService
    {
        return app(RsmiReportService::class);
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
     * Restricted to holders of rsmi_reports.print (Admin, Supply Officer);
     * Division Chief and Regional Director keep
     * view-only access to the module.
     */
    public function canPrintReport(): bool
    {
        return (bool) CurrentUser::get()?->hasPermissionTo('rsmi_reports.print');
    }

    public function canDownloadReport(): bool
    {
        return $this->canPrintReport();
    }

    public function printReport(): void
    {
        abort_unless($this->canPrintReport(), 403);

        $url = route('ris.rsmi', array_filter($this->filters ?? []));
        $this->dispatch('open-print-url', url: $url);
    }

    /**
     * Download the RSMI report as an A4-portrait DOCX document.
     *
     * The document is a faithful PhpWord rendering of
     * resources/views/ris/rsmi.blade.php — the same payload, the same labels
     * (via RsmiReportService constants) and the same Philippine Standard Time
     * timestamp (via ReportClock) that the Print action produces, so the two
     * outputs are identical in content.
     *
     * Strictly read-only: no inventory, requisition or supply data is
     * modified.
     */
    public function downloadReport(): StreamedResponse
    {
        abort_unless($this->canDownloadReport(), 403);

        $report = $this->reportPayload();

        $phpWord = $this->buildReportDocument($report);

        $filename = 'RSMI-Report-' . ReportClock::fileDate() . '.docx';

        return response()->streamDownload(function () use ($phpWord) {
            IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * The single report payload shared with the Print action.
     *
     * @return array<string, mixed>
     */
    public function reportPayload(): array
    {
        return $this->service()->build(
            $this->filters ?? [],
            CurrentUser::get()?->name,
        );
    }

    /**
     * Build the downloadable RSMI document (A4 portrait, DOCX).
     *
     * Mirrors resources/views/ris/rsmi.blade.php row for row: the same header
     * block, one single fixed-layout table holding the title row, the
     * division-of-responsibility note, the eight main columns, the grand total,
     * the recapitulation block (two-column spans, as in the print view), the
     * certification statement and both signatories.
     *
     * @param  array<string, mixed>  $report  Payload from RsmiReportService::build()
     */
    public function buildReportDocument(array $report): PhpWord
    {
        $svc = RsmiReportService::class;

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);

        // A4 portrait, 1.2cm margins — mirrors the print view's @page rule and
        // leaves 18.6cm of usable width for the table.
        $usableWidth = 18.6;

        $section = $phpWord->addSection([
            'orientation' => 'portrait',
            'pageSizeW' => Converter::cmToTwip(21.0),
            'pageSizeH' => Converter::cmToTwip(29.7),
            'marginTop' => Converter::cmToTwip(1.2),
            'marginBottom' => Converter::cmToTwip(1.2),
            'marginLeft' => Converter::cmToTwip(1.2),
            'marginRight' => Converter::cmToTwip(1.2),
        ]);

        // ── Header block (same order and wording as the print view) ──
        $section->addText(
            $svc::APPENDIX,
            ['italic' => true, 'size' => 9, 'color' => '000000'],
            ['alignment' => 'right', 'spaceAfter' => 40]
        );
        $section->addText(
            $svc::TITLE,
            ['bold' => true, 'size' => 14, 'color' => '000000'],
            ['alignment' => 'center', 'spaceAfter' => 60]
        );
        $section->addText(
            'Entity Name: ' . $svc::ENTITY_NAME . '     Fund Cluster: ' . $svc::FUND_CLUSTER,
            ['size' => 9, 'color' => '000000'],
            ['alignment' => 'center', 'spaceAfter' => 40]
        );

        if ($report['period_label'] !== null) {
            $section->addText(
                $report['period_label'] . ' ' . $report['period'],
                ['size' => 9, 'color' => '000000'],
                ['alignment' => 'center', 'spaceAfter' => 40]
            );
        }

        // Same Philippine Standard Time value the print view renders.
        $section->addText(
            'Generated: ' . $report['generated_at'] . ' (PST)',
            ['size' => 9, 'color' => '000000'],
            ['alignment' => 'center', 'spaceAfter' => 160]
        );

        // Column widths mirror the print view's 12/10/12/25/8/8/12/13 split,
        // converted to centimetres against the 18.6cm usable width.
        $percentages = [12, 10, 12, 25, 8, 8, 12, 13];
        $tableWidths = array_map(
            fn (int $pct): int => Converter::cmToTwip(round($usableWidth * $pct / 100, 4)),
            $percentages
        );

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'layout' => TableStyle::LAYOUT_FIXED,
            'width' => (int) array_sum($tableWidths),
            'unit' => TblWidth::TWIP,
        ]);

        $cellStyle = ['valign' => 'center'];
        $shaded = ['bgColor' => 'F0F0F0'];

        // Title row spanning all eight columns.
        $table->addRow(null, ['cantSplit' => true])
            ->addCell(null, $cellStyle + ['gridSpan' => 8])
            ->addText($svc::TITLE, ['bold' => true, 'size' => 12], ['alignment' => 'center']);

        // Division-of-responsibility note row (6 + 2, as in the print view).
        $noteRow = $table->addRow(null, ['cantSplit' => true]);
        $noteRow->addCell(null, $cellStyle + ['gridSpan' => 6])
            ->addText($svc::SUPPLY_NOTE, ['size' => 8], ['alignment' => 'left']);
        $noteRow->addCell(null, $cellStyle + ['gridSpan' => 2])
            ->addText($svc::ACCOUNTING_NOTE, ['size' => 8], ['alignment' => 'left']);

        $headerRow = $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        foreach ($svc::HEADERS as $index => $header) {
            $headerRow
                ->addCell($tableWidths[$index], $cellStyle + $shaded)
                ->addText($header, ['bold' => true, 'size' => 8.5], ['alignment' => 'center']);
        }

        if ($report['items'] === []) {
            $table->addRow(null, ['cantSplit' => true])
                ->addCell(null, $cellStyle + ['gridSpan' => 8])
                ->addText('No records found.', ['italic' => true], ['alignment' => 'center']);
        }

        foreach ($report['items'] as $row) {
            $row_out = $table->addRow(null, ['cantSplit' => true]);
            $row_out->addCell($tableWidths[0], $cellStyle)
                ->addText($row['ris_no'], null, ['alignment' => 'center']);
            $row_out->addCell($tableWidths[1], $cellStyle)
                ->addText($row['responsibility_center_code'], null, ['alignment' => 'center']);
            $row_out->addCell($tableWidths[2], $cellStyle)
                ->addText($row['stock_no'], null, ['alignment' => 'center']);
            $row_out->addCell($tableWidths[3], $cellStyle)
                ->addText($row['item_description'], null, ['alignment' => 'center']);
            $row_out->addCell($tableWidths[4], $cellStyle)
                ->addText($row['unit'], null, ['alignment' => 'center']);
            $row_out->addCell($tableWidths[5], $cellStyle)
                ->addText($row['quantity'], null, ['alignment' => 'center']);
            $row_out->addCell($tableWidths[6], $cellStyle)
                ->addText($row['unit_cost'], null, ['alignment' => 'right']);
            $row_out->addCell($tableWidths[7], $cellStyle)
                ->addText($row['amount'], null, ['alignment' => 'right']);
        }

        $totalRow = $table->addRow(null, ['cantSplit' => true]);
        $totalRow->addCell(null, $cellStyle + $shaded + ['gridSpan' => 5])
            ->addText('Grand Total', ['bold' => true, 'size' => 8.5]);
        $totalRow->addCell($tableWidths[5], $cellStyle + $shaded)
            ->addText($report['grand_total_qty'], ['bold' => true, 'size' => 8.5], ['alignment' => 'center']);
        $totalRow->addCell($tableWidths[6], $cellStyle + $shaded)
            ->addText('');
        $totalRow->addCell($tableWidths[7], $cellStyle + $shaded)
            ->addText($report['grand_total_amount'], ['bold' => true, 'size' => 8.5], ['alignment' => 'right']);

        // Spacer row (the print view uses height:15px here).
        $table->addRow(null, ['cantSplit' => true])
            ->addCell(null, ['gridSpan' => 8])
            ->addText('', null, ['spaceAfter' => 120]);

        // ── Recapitulation, inside the same table, two-column spans ──
        $table->addRow(null, ['cantSplit' => true])
            ->addCell(null, $cellStyle + ['gridSpan' => 8])
            ->addText('Recapitulation', ['bold' => true, 'size' => 10], ['alignment' => 'center']);

        $recapHeaderRow = $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        foreach ($svc::RECAP_HEADERS as $header) {
            $recapHeaderRow
                ->addCell(null, $cellStyle + $shaded + ['gridSpan' => 2])
                ->addText($header, ['bold' => true, 'size' => 8.5], ['alignment' => 'center']);
        }

        foreach ($report['recapitulation'] as $item) {
            $recapRow = $table->addRow(null, ['cantSplit' => true]);
            $recapRow->addCell(null, $cellStyle + ['gridSpan' => 2])
                ->addText($item['stock_number'], null, ['alignment' => 'center']);
            $recapRow->addCell(null, $cellStyle + ['gridSpan' => 2])
                ->addText(number_format($item['quantity']), null, ['alignment' => 'center']);
            $recapRow->addCell(null, $cellStyle + ['gridSpan' => 2])
                ->addText($item['unit_cost'], null, ['alignment' => 'right']);
            $recapRow->addCell(null, $cellStyle + ['gridSpan' => 2])
                ->addText($item['total_cost'], null, ['alignment' => 'right']);
        }

        $recapTotal = $table->addRow(null, ['cantSplit' => true]);
        $recapTotal->addCell(null, $cellStyle + $shaded + ['gridSpan' => 2])
            ->addText('Total', ['bold' => true, 'size' => 8.5]);
        $recapTotal->addCell(null, $cellStyle + $shaded + ['gridSpan' => 2])
            ->addText($report['recap_total_qty'], ['bold' => true, 'size' => 8.5], ['alignment' => 'center']);
        $recapTotal->addCell(null, $cellStyle + $shaded + ['gridSpan' => 2])
            ->addText('');
        $recapTotal->addCell(null, $cellStyle + $shaded + ['gridSpan' => 2])
            ->addText($report['recap_total_cost'], ['bold' => true, 'size' => 8.5], ['alignment' => 'right']);

        $overallRow = $table->addRow(null, ['cantSplit' => true]);
        $overallRow->addCell(null, $cellStyle + $shaded + ['gridSpan' => 6])
            ->addText('Overall Total Cost', ['bold' => true, 'size' => 8.5], ['alignment' => 'right']);
        $overallRow->addCell(null, $cellStyle + $shaded + ['gridSpan' => 2])
            ->addText($report['recap_total_cost'], ['bold' => true, 'size' => 8.5], ['alignment' => 'right']);

        // Spacer row (the print view uses height:30px here).
        $table->addRow(null, ['cantSplit' => true])
            ->addCell(null, ['gridSpan' => 8])
            ->addText('', null, ['spaceAfter' => 240]);

        // ── Certification and signatories ──
        $signRow = $table->addRow(null, ['cantSplit' => true]);

        $leftCell = $signRow->addCell(null, ['gridSpan' => 4, 'valign' => 'top']);
        $leftCell->addText($svc::CERTIFICATION, ['size' => 8.5], ['alignment' => 'left', 'spaceAfter' => 200]);
        $leftCell->addText(
            $report['issued_by'] ?? '________________________',
            ['size' => 8.5],
            ['alignment' => 'center', 'spaceAfter' => 20]
        );
        $leftCell->addText($svc::CUSTODIAN_LABEL, ['size' => 8], ['alignment' => 'center']);

        $rightCell = $signRow->addCell(null, ['gridSpan' => 4, 'valign' => 'top']);
        $rightCell->addText('', null, ['spaceAfter' => 200]);
        $rightCell->addText('Ana Fe B. Galanto', ['size' => 8.5], ['alignment' => 'center', 'spaceAfter' => 20]);
        $rightCell->addText($svc::ACCOUNTING_STAFF_LABEL, ['size' => 8], ['alignment' => 'center']);

        return $phpWord;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printReport')
                ->label('Print RSMI')
                ->icon('heroicon-o-printer')
                ->visible(fn (): bool => $this->canPrintReport())
                ->action('printReport'),
            Action::make('downloadReport')
                ->label('Download RSMI')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => $this->canDownloadReport())
                ->action('downloadReport'),
        ];
    }
}
