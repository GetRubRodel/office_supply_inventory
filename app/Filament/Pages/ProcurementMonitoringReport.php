<?php

namespace App\Filament\Pages;

use App\Services\ProcurementMonitoringService;
use App\Support\CurrentUser;
use App\Support\ReportClock;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table as TableStyle;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Procurement Monitoring Report.
 *
 * A read-only report that pulls data automatically from the existing
 * Purchase Request (PR) and Purchase Order (PO) modules. PR/PO records,
 * relationships and the database structure are never modified.
 */
class ProcurementMonitoringReport extends Page
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('procurement_monitoring_reports.view');
    }

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Report Management';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.procurement-monitoring-report';

    public ?array $filters = [];

    public ?int $viewingRecordId = null;

    private ?Collection $cachedRows = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('date_from')
                    ->label('Date Requested From'),
                DatePicker::make('date_to')
                    ->label('Date Requested To'),
                Select::make('office_division')
                    ->label('Office / Division')
                    ->options([
                        'Administrative Division' => 'Administrative Division',
                        'Promotion and Advocacy Division' => 'Promotion and Advocacy Division',
                        'Investigation Division' => 'Investigation Division',
                        'Legal Division' => 'Legal Division',
                        'Commission on Human Rights XII' => 'Commission on Human Rights XII',
                    ])
                    ->placeholder('All Divisions'),
            ])
            ->statePath('filters');
    }

    /**
     * Report rows (deduplicated by PR, latest PO shown).
     */
    public function getRows(): Collection
    {
        return $this->cachedRows ??= app(ProcurementMonitoringService::class)->rows($this->filters ?? []);
    }

    public function applyFilters(): void
    {
        $this->cachedRows = null;
        $this->dispatch('$refresh');
    }

    public function resetFilters(): void
    {
        $this->form->fill();
        $this->cachedRows = null;
        $this->dispatch('$refresh');
    }

    public function getViewingRecord(): ?array
    {
        return $this->getRows()->firstWhere('id', $this->viewingRecordId);
    }

    public function openView(int $recordId): void
    {
        $this->viewingRecordId = $recordId;
        $this->dispatch('open-modal', id: 'view-record');
    }

    public function closeView(): void
    {
        $this->viewingRecordId = null;
        $this->dispatch('close-modal', id: 'view-record');
    }

    /**
     * Whether the current user may print or download this report.
     * Restricted to holders of procurement_monitoring_reports.print (Admin,
     * Supply Officer); Division Chief and Regional
     * Director keep view-only access to the module.
     */
    public function canPrintReport(): bool
    {
        return (bool) CurrentUser::get()?->hasPermissionTo('procurement_monitoring_reports.print');
    }

    public function canDownloadReport(): bool
    {
        return $this->canPrintReport();
    }

    public function printReport(): void
    {
        abort_unless($this->canPrintReport(), 403);

        $url = route('procurement_monitoring.print', array_filter($this->filters ?? []));
        $this->dispatch('open-print-url', url: $url);
    }

    /**
     * Download the report as an A4-landscape DOCX document. Only reads the
     * exact data the authenticated user is authorized to see.
     */
    public function downloadReport(): StreamedResponse
    {
        abort_unless($this->canDownloadReport(), 403);

        $rows = $this->getRows();
        $phpWord = $this->buildReportDocument($rows);

        $filename = 'Procurement-Monitoring-Report-' . ReportClock::fileDate() . '.docx';

        return response()->streamDownload(function () use ($phpWord) {
            IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    protected function buildReportDocument(Collection $rows): PhpWord
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);

        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => Converter::cmToTwip(29.7),
            'pageSizeH' => Converter::cmToTwip(21.0),
            'marginTop' => Converter::cmToTwip(1.27),
            'marginBottom' => Converter::cmToTwip(1.27),
            'marginLeft' => Converter::cmToTwip(1.0),
            'marginRight' => Converter::cmToTwip(1.0),
        ]);

        $section->addText(
            'PROCUREMENT MONITORING REPORT',
            ['bold' => true, 'size' => 16, 'color' => '000000'],
            ['alignment' => 'center', 'spaceAfter' => 40]
        );
        $section->addText(
            'Commission on Human Rights Region XII',
            ['size' => 10, 'color' => '333333'],
            ['alignment' => 'center', 'spaceAfter' => 40]
        );
        $section->addText(
            ReportClock::generatedOn(),
            ['size' => 9, 'italic' => true, 'color' => '444444'],
            ['alignment' => 'center', 'spaceAfter' => 200]
        );

        $tableWidths = [
            Converter::cmToTwip(2.6), // Date Requested
            Converter::cmToTwip(2.4), // Control No.
            Converter::cmToTwip(2.4), // P.O. Date
            Converter::cmToTwip(2.4), // IAR Date
            Converter::cmToTwip(6.6), // Particulars
            Converter::cmToTwip(4.2), // Responsibility Center
            Converter::cmToTwip(3.2), // Source of Fund
            Converter::cmToTwip(3.0), // Gross Amount
        ];

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'layout' => TableStyle::LAYOUT_FIXED,
            'width' => (int) array_sum($tableWidths),
            'unit' => TblWidth::TWIP,
        ]);

        $cellStyle = ['valign' => 'center'];

        $headers = [
            'Date Requested',
            'Control No.',
            'P.O. Date',
            'IAR Date',
            'Particulars',
            'Responsibility Center',
            'Source of Fund',
            'Gross Amount',
        ];

        $headerRow = $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        foreach ($headers as $index => $header) {
            $headerRow
                ->addCell($tableWidths[$index], $cellStyle + ['bgColor' => 'D9D9D9'])
                ->addText($header, ['bold' => true, 'size' => 9], ['alignment' => 'center']);
        }

        if ($rows->isEmpty()) {
            $emptyRow = $table->addRow(null, ['cantSplit' => true]);
            $emptyRow
                ->addCell(null, $cellStyle + ['gridSpan' => count($headers)])
                ->addText('No procurement records found.', ['italic' => true], ['alignment' => 'center']);
        }

        foreach ($rows as $row) {
            $tr = $table->addRow(null, ['cantSplit' => true]);

            $tr->addCell($tableWidths[0], $cellStyle)
                ->addText($row['date_requested']?->format('m/d/Y') ?? '—', null, ['alignment' => 'center']);
            $tr->addCell($tableWidths[1], $cellStyle)
                ->addText((string) $row['control_number'], null, ['alignment' => 'center']);
            $tr->addCell($tableWidths[2], $cellStyle)
                ->addText($row['po_date']?->format('m/d/Y') ?? '—', null, ['alignment' => 'center']);
            $tr->addCell($tableWidths[3], $cellStyle)
                ->addText($row['iar_date']?->format('m/d/Y') ?? '—', null, ['alignment' => 'center']);
            $tr->addCell($tableWidths[4], $cellStyle)
                ->addText((string) ($row['particulars'] ?: '—'));
            $tr->addCell($tableWidths[5], $cellStyle)
                ->addText((string) ($row['responsibility_center'] ?: '—'));
            $tr->addCell($tableWidths[6], $cellStyle)
                ->addText((string) ($row['source_of_fund'] ?: '—'));
            $tr->addCell($tableWidths[7], $cellStyle)
                ->addText(number_format((float) $row['gross_amount'], 2), null, ['alignment' => 'right']);
        }

        $totalRow = $table->addRow(null, ['cantSplit' => true]);
        $totalRow->addCell(null, $cellStyle + ['gridSpan' => 7, 'bgColor' => 'D9D9D9'])
            ->addText('GRAND TOTAL', ['bold' => true, 'size' => 9], ['alignment' => 'right']);
        $totalRow->addCell($tableWidths[7], $cellStyle + ['bgColor' => 'D9D9D9'])
            ->addText(number_format((float) $rows->sum('gross_amount'), 2), ['bold' => true, 'size' => 9], ['alignment' => 'right']);

        $footer = $section->addFooter();
        $footer->addText('Page ', ['size' => 8, 'color' => '666666'], ['alignment' => 'center']);
        $footer->addField('PAGE', [], [], '1');

        return $phpWord;
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
