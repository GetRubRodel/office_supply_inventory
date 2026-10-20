<?php

namespace Tests\Feature;

use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\User;
use App\Services\ProcurementMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProcurementMonitoringReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mirror RolePermissionSeeder's full permission set so sidebar
        // navigation never hits Spatie's PermissionDoesNotExist.
        $modules = ['dashboard', 'users', 'supplies', 'suppliers', 'categories', 'purchase_requests', 'request_for_quotations', 'abstracts_of_canvass', 'purchase_orders', 'inspection_acceptance_reports', 'bac_resolutions', 'requisitions', 'rpcis', 'rsmi_reports', 'inventory_reports', 'procurement_monitoring_reports', 'audit_logs', 'system_settings', 'stock_in'];
        foreach ($modules as $module) {
            foreach (['view', 'create', 'update', 'delete', 'approve'] as $action) {
                Permission::findOrCreate("{$module}.{$action}", 'web');
            }
        }
        foreach (['requisitions.issue', 'requisitions.receive', 'purchase_requests.print', 'requisitions.print', 'request_for_quotations.print', 'abstracts_of_canvass.print', 'purchase_orders.print', 'inspection_acceptance_reports.print', 'bac_resolutions.print', 'rpcis.print', 'rpcis.preview', 'inspection_acceptance_reports.stock_in', 'inventory_reports.print', 'rsmi_reports.print', 'procurement_monitoring_reports.view', 'procurement_monitoring_reports.print'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $rolePermissions = [
            'admin' => Permission::all()->pluck('name')->all(),
            'supply_officer' => ['dashboard.view', 'purchase_requests.view', 'purchase_orders.view', 'procurement_monitoring_reports.view', 'procurement_monitoring_reports.print'],
            'division_chief' => ['dashboard.view', 'purchase_requests.view', 'purchase_orders.view', 'procurement_monitoring_reports.view'],
            'regional_director' => ['dashboard.view', 'purchase_requests.view', 'purchase_orders.view', 'procurement_monitoring_reports.view'],
            // Staff must NOT see the report module.
            'staff' => ['dashboard.view', 'purchase_requests.view', 'purchase_requests.create'],
        ];

        foreach ($rolePermissions as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'approved',
        ]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Build the full PR -> RFQ -> ABC -> BAC Resolution -> PO document chain.
     */
    private function makeLinkedPair(array $overrides = []): array
    {
        $owner = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
        ]);

        $pr = PurchaseRequest::create(array_merge([
            'user_id' => $owner->id,
            'date' => '2026-08-01',
            'office_division' => 'Administrative Division',
            'name_of_project' => 'Supply of Office Materials',
            'purpose' => 'Replenish office materials',
            'source_of_fund' => 'GAA',
            'status' => PurchaseRequest::STATUS_APPROVED,
        ], $overrides['pr'] ?? []));

        $rfq = RequestForQuotation::create([
            'purchase_request_id' => $pr->id,
            'date' => '2026-08-05',
        ]);

        $abc = AbstractOfCanvass::create([
            'rfq_id' => $rfq->id,
        ]);

        $bac = BacResolution::create([
            'abc_id' => $abc->id,
            'date' => '2026-08-08',
        ]);

        $po = PurchaseOrder::create(array_merge([
            'bac_resolution_id' => $bac->id,
            'abc_id' => $abc->id,
            'date' => '2026-08-10',
            'supplier_name' => 'Test Supplier',
            'total_amount' => 25259.50,
        ], $overrides['po'] ?? []));

        $iar = null;
        if (array_key_exists('iar', $overrides)) {
            $iarOverrides = $overrides['iar'] === true ? [] : $overrides['iar'];
            $iar = InspectionAcceptanceReport::create(array_merge([
                'po_id' => $po->id,
                'date' => '2026-08-18',
            ], $iarOverrides));
        }

        return compact('pr', 'rfq', 'abc', 'bac', 'po', 'iar');
    }

    // ─── Access control ─────────────────────────────────────────────

    public function test_report_roles_can_open_the_page(): void
    {
        $pair = $this->makeLinkedPair(['iar' => ['date' => '2026-08-18']]);

        foreach (['admin', 'supply_officer', 'division_chief', 'regional_director'] as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)
                ->get('/admin/procurement-monitoring-report')
                ->assertOk()
                ->assertSee('2026-0001', false)
                ->assertSee('Supply of Office Materials', false)
                ->assertSee('IAR Date', false)
                ->assertSee('08/18/2026', false);
        }
    }

    public function test_staff_cannot_open_the_page(): void
    {
        $this->makeLinkedPair();
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)
            ->get('/admin/procurement-monitoring-report')
            ->assertForbidden();
    }

    // ─── Data: control number, linkage, dedupe, latest data ─────────

    public function test_control_number_shows_pr_number_without_prefix(): void
    {
        $pair = $this->makeLinkedPair();

        $rows = app(ProcurementMonitoringService::class)->rows();

        $this->assertCount(1, $rows);
        $this->assertSame('2026-0001', $rows->first()['control_number']);
        $this->assertSame('Administrative Division', $rows->first()['responsibility_center']);
        $this->assertSame('Supply of Office Materials', $rows->first()['particulars']);
        $this->assertSame('GAA', $rows->first()['source_of_fund']);
        $this->assertSame(25259.50, $rows->first()['gross_amount']);
    }

    public function test_iar_date_is_retrieved_from_the_linked_iar(): void
    {
        $pair = $this->makeLinkedPair(['iar' => ['date' => '2026-08-18']]);

        $rows = app(ProcurementMonitoringService::class)->rows();

        $this->assertCount(1, $rows);
        $this->assertSame('2026-08-18', $rows->first()['iar_date']?->toDateString());
        // The IAR Date comes from the IAR linked to the PO shown in the report.
        $this->assertSame((int) $pair['po']->id, (int) $rows->first()['po']->id);
        $this->assertSame((int) $pair['iar']->po_id, (int) $rows->first()['po']->id);
    }

    public function test_iar_date_is_blank_when_po_has_no_iar(): void
    {
        $this->makeLinkedPair();

        $rows = app(ProcurementMonitoringService::class)->rows();

        $this->assertCount(1, $rows);
        $this->assertNull($rows->first()['iar_date']);
    }

    public function test_duplicate_entries_are_prevented_and_latest_po_wins(): void
    {
        // First PO for the PR.
        $pair = $this->makeLinkedPair(['iar' => ['date' => '2026-08-12']]);

        // Second (newer) PO for the same PR — the report must keep only one
        // row per PR and show the newest PO. With the strict PR -> RFQ -> ABC
        // -> BAC -> PO chain, a second PO for the same PR can only exist as
        // legacy data, so it is inserted through the query builder to bypass
        // the model-level source guard. bac_resolution_id is left null because
        // it is unique per PO.
        PurchaseOrder::query()->insert([
            'po_no' => 'PO-2026-9999',
            'abc_id' => $pair['abc']->id,
            'bac_resolution_id' => null,
            'date' => '2026-08-15',
            'supplier_name' => 'Newest Supplier',
            'total_amount' => 99999.99,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newerPo = PurchaseOrder::where('po_no', 'PO-2026-9999')->firstOrFail();

        InspectionAcceptanceReport::create([
            'po_id' => $newerPo->id,
            'date' => '2026-08-20',
        ]);

        $rows = app(ProcurementMonitoringService::class)->rows();

        $this->assertCount(1, $rows);
        $this->assertSame(99999.99, $rows->first()['gross_amount']);
        // The IAR Date follows the latest PO shown in the report.
        $this->assertSame('2026-08-20', $rows->first()['iar_date']?->toDateString());
    }

    public function test_unlinked_po_is_excluded(): void
    {
        $this->makeLinkedPair();

        // A PO that does not link back to any PR (no BAC Resolution/ABC).
        // Inserted through the query builder so it bypasses the model-level
        // source guard — it represents legacy data created before the
        // document dependency rules existed.
        PurchaseOrder::query()->insert([
            'po_no' => 'PO-2026-9999',
            'abc_id' => null,
            'bac_resolution_id' => null,
            'date' => '2026-08-20',
            'supplier_name' => 'Orphan Supplier',
            'total_amount' => 111.00,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rows = app(ProcurementMonitoringService::class)->rows();

        // Only the linked pair appears; the orphan PO is excluded.
        $this->assertCount(1, $rows);
        $this->assertSame(25259.50, $rows->first()['gross_amount']);
    }

    public function test_filters_apply_to_pr_date_and_office(): void
    {
        $pair = $this->makeLinkedPair();

        $this->assertCount(1, app(ProcurementMonitoringService::class)->rows(['office_division' => 'Administrative Division']));
        $this->assertCount(0, app(ProcurementMonitoringService::class)->rows(['office_division' => 'Legal Division']));
        $this->assertCount(1, app(ProcurementMonitoringService::class)->rows(['date_from' => '2026-08-01', 'date_to' => '2026-08-31']));
        $this->assertCount(0, app(ProcurementMonitoringService::class)->rows(['date_from' => '2027-01-01']));
    }

    // ─── Print + Download ───────────────────────────────────────────

    public function test_print_route_respects_permission(): void
    {
        $this->makeLinkedPair(['iar' => ['date' => '2026-08-18']]);

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)
            ->get(route('procurement_monitoring.print'))
            ->assertOk()
            ->assertSee('Procurement Monitoring Report', false)
            ->assertSee('2026-0001', false)
            ->assertSee('IAR Date', false)
            ->assertSee('08/18/2026', false);

        $staff = $this->makeUser('staff');
        $this->actingAs($staff)
            ->get(route('procurement_monitoring.print'))
            ->assertForbidden();

        // Division Chief and Regional Director keep view access to the page
        // but cannot print/download the report (permissions revoked).
        foreach (['division_chief', 'regional_director'] as $role) {
            $user = $this->makeUser($role);
            $this->actingAs($user)
                ->get('/admin/procurement-monitoring-report')
                ->assertOk()
                ->assertDontSee('Print Report', false)
                ->assertDontSee('Download Report', false);
            $this->actingAs($user)
                ->get(route('procurement_monitoring.print'))
                ->assertForbidden();
        }
    }

    public function test_print_and_download_are_denied_for_division_chief_and_regional_director(): void
    {
        $this->makeLinkedPair();

        foreach (['division_chief', 'regional_director'] as $role) {
            $user = $this->makeUser($role);

            $this->assertTrue($user->hasPermissionTo('procurement_monitoring_reports.view'));
            $this->assertFalse($user->hasPermissionTo('procurement_monitoring_reports.print'));

            try {
                app(\App\Filament\Pages\ProcurementMonitoringReport::class)->downloadReport();
                $this->fail("{$role} download should abort with 403.");
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
    }

    public function test_download_returns_docx(): void
    {
        $this->makeLinkedPair();

        $admin = $this->makeUser('admin');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\ProcurementMonitoringReport::class)
            ->call('downloadReport')
            ->assertFileDownloaded('Procurement-Monitoring-Report-' . now()->format('Y-m-d') . '.docx');
    }

    // ─── Read-only guarantee ────────────────────────────────────────

    public function test_loading_the_report_does_not_modify_records(): void
    {
        $pair = $this->makeLinkedPair(['iar' => ['date' => '2026-08-18']]);

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)
            ->get('/admin/procurement-monitoring-report')
            ->assertOk();

        $this->assertSame(1, PurchaseRequest::count());
        $this->assertSame(1, PurchaseOrder::count());
        $this->assertSame(1, InspectionAcceptanceReport::count());
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $pair['po']->id,
            'total_amount' => 25259.50,
        ]);
        $this->assertDatabaseHas('purchase_requests', [
            'id' => $pair['pr']->id,
            'status' => PurchaseRequest::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('inspection_acceptance_reports', [
            'id' => $pair['iar']->id,
        ]);
        $this->assertSame('2026-08-18', $pair['iar']->fresh()->date->toDateString());
    }
}
