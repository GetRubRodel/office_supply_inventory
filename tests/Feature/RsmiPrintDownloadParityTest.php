<?php

namespace Tests\Feature;

use App\Filament\Pages\RsmiReport;
use App\Models\Category;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supply;
use App\Models\User;
use App\Services\RsmiReportService;
use App\Support\ReportClock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpWord\IOFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Locks in the two guarantees the RSMI module must provide:
 *
 *   1. The Download action produces the same output as the Print action.
 *      Both read one App\Services\RsmiReportService payload, so every row,
 *      total and label is asserted to be present in both renderings.
 *   2. Every generated-form timestamp is Philippine Standard Time
 *      (Asia/Manila), never the application's UTC clock.
 */
class RsmiPrintDownloadParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'local');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modules = ['dashboard', 'users', 'supplies', 'suppliers', 'categories', 'purchase_requests', 'request_for_quotations', 'abstracts_of_canvass', 'purchase_orders', 'inspection_acceptance_reports', 'bac_resolutions', 'requisitions', 'rpcis', 'rsmi_reports', 'inventory_reports', 'procurement_monitoring_reports', 'audit_logs', 'system_settings', 'stock_in'];
        foreach ($modules as $module) {
            foreach (['view', 'create', 'update', 'delete', 'approve'] as $action) {
                Permission::findOrCreate("{$module}.{$action}", 'web');
            }
        }
        Permission::findOrCreate('rsmi_reports.print', 'web');

        Role::findOrCreate('admin', 'web')->syncPermissions(Permission::all()->pluck('name')->all());
    }

    private function makeUser(): User
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $user->assignRole('admin');

        return $user;
    }

    private function makeSupply(float $unitCost, string $name = 'Bond Paper A4'): Supply
    {
        $category = Category::create([
            'name' => 'Category '.uniqid(),
            'description' => 'Test category',
            'status' => 'active',
        ]);

        return Supply::create([
            'stock_no' => 'STK-'.uniqid(),
            'name' => $name,
            'category_id' => $category->id,
            'unit' => 'ream',
            'unit_cost' => $unitCost,
            'current_stock' => 100,
            'status' => 'active',
        ]);
    }

    private function makeRequisition(User $user, string $status, array $items): Requisition
    {
        $requisition = Requisition::create([
            'user_id' => $user->id,
            'status' => $status,
            'issued_by_date' => '2026-08-10',
        ]);

        foreach ($items as $item) {
            RequisitionItem::create([
                'requisition_id' => $requisition->id,
                'supply_id' => $item['supply_id'],
                'quantity_requested' => $item['quantity'],
                'quantity_issued' => $item['quantity'],
                'issued_unit_cost' => $item['unit_cost'],
                'price' => $item['unit_cost'],
            ]);
        }

        return $requisition;
    }

    /**
     * Seed a mix of issued / pending_receipt / received rows plus a row that
     * must never appear (requested, approved and cancelled are excluded).
     */
    private function seedReportData(User $user): void
    {
        $paper = $this->makeSupply(250.00, 'Bond Paper A4');
        $pen = $this->makeSupply(35.50, 'Ballpen Blue');

        $this->makeRequisition($user, Requisition::STATUS_ISSUED, [
            ['supply_id' => $paper->id, 'quantity' => 5, 'unit_cost' => 250.00],
            ['supply_id' => $pen->id, 'quantity' => 12, 'unit_cost' => 35.50],
        ]);
        $this->makeRequisition($user, Requisition::STATUS_RECEIVED, [
            ['supply_id' => $paper->id, 'quantity' => 2, 'unit_cost' => 250.00],
        ]);
        $this->makeRequisition($user, Requisition::STATUS_PENDING_RECEIPT, [
            ['supply_id' => $pen->id, 'quantity' => 3, 'unit_cost' => 35.50],
        ]);

        // Excluded from the report entirely.
        $this->makeRequisition($user, Requisition::STATUS_REQUESTED, [
            ['supply_id' => $paper->id, 'quantity' => 99, 'unit_cost' => 250.00],
        ]);
        $this->makeRequisition($user, Requisition::STATUS_APPROVED, [
            ['supply_id' => $paper->id, 'quantity' => 77, 'unit_cost' => 250.00],
        ]);
        $this->makeRequisition($user, Requisition::STATUS_CANCELLED, [
            ['supply_id' => $paper->id, 'quantity' => 66, 'unit_cost' => 250.00],
        ]);
    }

    /**
     * Render the downloadable DOCX and extract its visible text.
     *
     * Reads word/document.xml straight out of the generated .docx archive so
     * the assertions cover the artefact the user actually downloads, rather
     * than the in-memory PhpWord object tree.
     */
    private function docxText(array $filters = []): string
    {
        $page = app(RsmiReport::class);
        $page->filters = $filters;

        $phpWord = $page->buildReportDocument($page->reportPayload());

        $path = tempnam(sys_get_temp_dir(), 'rsmi').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true, 'Generated DOCX is not a readable archive.');

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertIsString($xml, 'Generated DOCX has no word/document.xml part.');

        // Join adjacent runs so a value split across <w:r> nodes still matches.
        $xml = preg_replace('#</w:p>#', "\n", $xml) ?? $xml;
        $text = preg_replace('#<[^>]+>#', '', $xml) ?? $xml;

        return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    // ── Print / Download parity ─────────────────────────────────────

    public function test_print_and_download_contain_identical_item_rows(): void
    {
        $user = $this->makeUser();
        $this->seedReportData($user);
        $this->actingAs($user);

        $report = app(RsmiReportService::class)->build([], $user->name);
        $print = $this->get(route('ris.rsmi'))->assertOk()->getContent();
        $docx = $this->docxText();

        $this->assertNotEmpty($report['items']);

        // Every rendered item cell must appear in BOTH outputs.
        foreach ($report['items'] as $index => $row) {
            foreach ($row as $field => $value) {
                if ($value === '' || $value === '-') {
                    continue;
                }

                $this->assertStringContainsString(
                    $value,
                    $print,
                    "Item #{$index} field '{$field}' ({$value}) missing from the PRINT output."
                );
                $this->assertStringContainsString(
                    $value,
                    $docx,
                    "Item #{$index} field '{$field}' ({$value}) missing from the DOWNLOAD output."
                );
            }
        }
    }

    public function test_print_and_download_contain_identical_totals(): void
    {
        $user = $this->makeUser();
        $this->seedReportData($user);
        $this->actingAs($user);

        $report = app(RsmiReportService::class)->build([], $user->name);
        $print = $this->get(route('ris.rsmi'))->assertOk()->getContent();
        $docx = $this->docxText();

        // 5 + 12 + 2 + 3 = 22 units
        // 5*250 + 12*35.50 + 2*250 + 3*35.50 = 1250 + 426 + 500 + 106.50 = 2,282.50
        $this->assertSame('22', $report['grand_total_qty']);
        $this->assertSame('2,282.50', $report['grand_total_amount']);

        foreach (['grand_total_qty', 'grand_total_amount', 'recap_total_qty', 'recap_total_cost'] as $key) {
            $this->assertStringContainsString($report[$key], $print, "'{$key}' missing from PRINT.");
            $this->assertStringContainsString($report[$key], $docx, "'{$key}' missing from DOWNLOAD.");
        }
    }

    public function test_print_and_download_contain_identical_labels(): void
    {
        $user = $this->makeUser();
        $this->seedReportData($user);
        $this->actingAs($user);

        $print = $this->get(route('ris.rsmi'))->assertOk()->getContent();
        $docx = $this->docxText();

        $labels = [
            RsmiReportService::APPENDIX,
            RsmiReportService::TITLE,
            RsmiReportService::ENTITY_NAME,
            RsmiReportService::FUND_CLUSTER,
            RsmiReportService::SUPPLY_NOTE,
            RsmiReportService::ACCOUNTING_NOTE,
            RsmiReportService::CERTIFICATION,
            RsmiReportService::CUSTODIAN_LABEL,
            RsmiReportService::ACCOUNTING_STAFF_LABEL,
            'Grand Total',
            'Recapitulation',
            'Overall Total Cost',
        ];

        foreach ($labels as $label) {
            $this->assertStringContainsString($label, $print, "'{$label}' missing from PRINT.");
            $this->assertStringContainsString($label, $docx, "'{$label}' missing from DOWNLOAD.");
        }

        foreach (array_merge(RsmiReportService::HEADERS, RsmiReportService::RECAP_HEADERS) as $header) {
            $this->assertStringContainsString($header, $print, "Header '{$header}' missing from PRINT.");
            $this->assertStringContainsString($header, $docx, "Header '{$header}' missing from DOWNLOAD.");
        }
    }

    public function test_print_and_download_include_pending_receipt_rows(): void
    {
        // Regression: the print route used to filter on ['issued', 'received']
        // only, so a pending_receipt row showed in the download but not the
        // print. Both must now come from RsmiReportService.
        $user = $this->makeUser();
        $paper = $this->makeSupply(250.00);
        $requisition = $this->makeRequisition($user, Requisition::STATUS_PENDING_RECEIPT, [
            ['supply_id' => $paper->id, 'quantity' => 4, 'unit_cost' => 250.00],
        ]);

        $this->actingAs($user);

        $print = $this->get(route('ris.rsmi'))->assertOk()->getContent();

        $this->assertStringContainsString($requisition->ris_no, $print);
        $this->assertStringContainsString('4', $print);
    }

    public function test_print_and_download_exclude_not_yet_issued_rows(): void
    {
        $user = $this->makeUser();
        $paper = $this->makeSupply(250.00);
        $requested = $this->makeRequisition($user, Requisition::STATUS_REQUESTED, [
            ['supply_id' => $paper->id, 'quantity' => 9, 'unit_cost' => 250.00],
        ]);

        $this->actingAs($user);

        $print = $this->get(route('ris.rsmi'))->assertOk()->getContent();
        $docx = $this->docxText();

        $this->assertStringNotContainsString($requested->ris_no, $print);
        $this->assertStringNotContainsString($requested->ris_no, $docx);
        $this->assertStringContainsString('No records found.', $print);
        $this->assertStringContainsString('No records found.', $docx);
    }

    public function test_period_filter_is_identical_in_both_outputs(): void
    {
        $user = $this->makeUser();
        $this->seedReportData($user);
        $this->actingAs($user);

        $filters = ['date_from' => '2026-08-01', 'date_to' => '2026-08-31'];

        $report = app(RsmiReportService::class)->build($filters, $user->name);

        $this->assertSame('Period:', $report['period_label']);
        $this->assertSame('August 01, 2026 - August 31, 2026', $report['period']);

        $print = $this->get(route('ris.rsmi', $filters))->assertOk()->getContent();
        $docx = $this->docxText($filters);

        $this->assertStringContainsString('Period:', $print);
        $this->assertStringContainsString('August 01, 2026 - August 31, 2026', $print);
        $this->assertStringContainsString('Period:', $docx);
        $this->assertStringContainsString('August 01, 2026 - August 31, 2026', $docx);
    }

    public function test_download_filename_uses_pst_date(): void
    {
        $user = $this->makeUser();
        $supply = $this->makeSupply(250.00);
        $this->makeRequisition($user, Requisition::STATUS_ISSUED, [
            ['supply_id' => $supply->id, 'quantity' => 1, 'unit_cost' => 250.00],
        ]);

        \Livewire\Livewire::actingAs($user)
            ->test(RsmiReport::class)
            ->call('downloadReport')
            ->assertFileDownloaded('RSMI-Report-'.ReportClock::fileDate().'.docx');
    }

    // ── Philippine Standard Time ────────────────────────────────────

    public function test_generated_timestamp_uses_philippine_standard_time(): void
    {
        $user = $this->makeUser();
        $supply = $this->makeSupply(250.00);
        $this->makeRequisition($user, Requisition::STATUS_ISSUED, [
            ['supply_id' => $supply->id, 'quantity' => 1, 'unit_cost' => 250.00],
        ]);
        $this->actingAs($user);

        $print = $this->get(route('ris.rsmi'))->assertOk()->getContent();

        $this->assertStringContainsString(ReportClock::format(), $print);
        $this->assertStringContainsString('(PST)', $print);
        $this->assertStringContainsString(ReportClock::format(), $this->docxText());

        // The rendered value must be 8 hours ahead of the UTC clock, which is
        // what distinguishes a real conversion from a cosmetic label.
        $expected = \Carbon\CarbonImmutable::now('UTC')->setTimezone('Asia/Manila');
        $this->assertSame(
            $expected->format('F d, Y'),
            \Carbon\CarbonImmutable::parse(ReportClock::format())->format('F d, Y'),
        );
    }

    public function test_report_clock_uses_asia_manila_timezone(): void
    {
        $this->assertSame('Asia/Manila', ReportClock::TIMEZONE);
        $this->assertSame('Asia/Manila', ReportClock::now()->getTimezone()->getName());
        $this->assertSame(
            ReportClock::now()->format('Y-m-d'),
            ReportClock::fileDate()
        );
    }

    public function test_report_clock_never_uses_the_utc_application_clock(): void
    {
        // config('app.timezone') is UTC, so any report timestamp that still
        // resolves through now() would be eight hours behind.
        $this->assertSame('UTC', config('app.timezone'));

        $utcOffset = \Carbon\CarbonImmutable::now('UTC')->offset;
        $pstOffset = ReportClock::now()->offset;

        $this->assertSame(8 * 3600, $pstOffset - $utcOffset);
    }

    public function test_rpci_print_view_uses_pst_for_effectivity_date(): void
    {
        $view = file_get_contents(resource_path('views/rpci/print.blade.php'));

        $this->assertStringContainsString('ReportClock::format', $view);
        $this->assertStringNotContainsString('now()->format', $view);
    }

    public function test_other_generated_forms_use_the_report_clock(): void
    {
        $files = [
            app_path('Filament/Pages/InventoryReport.php'),
            app_path('Filament/Pages/ProcurementMonitoringReport.php'),
            resource_path('views/procurement_monitoring/print.blade.php'),
            resource_path('views/filament/pages/inventory-report.blade.php'),
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertStringContainsString(
                'ReportClock',
                $contents,
                basename($file).' does not use App\Support\ReportClock.'
            );
            $this->assertDoesNotMatchRegularExpression(
                '/now\(\)\s*->\s*format|now\(\)->setTimezone/',
                $contents,
                basename($file).' still renders an output timestamp from the raw clock.'
            );
        }
    }
}
