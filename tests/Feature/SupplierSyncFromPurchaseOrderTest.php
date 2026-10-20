<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SupplierSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplierSyncFromPurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The User model does not implement FilamentUser, so Filament's panel-auth
        // middleware permits access only when APP_ENV=local (as in production).
        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mirror RolePermissionSeeder's full permission set so sidebar navigation
        // never hits Spatie's PermissionDoesNotExist.
        $modules = ['dashboard', 'users', 'supplies', 'suppliers', 'categories', 'purchase_requests', 'request_for_quotations', 'abstracts_of_canvass', 'purchase_orders', 'inspection_acceptance_reports', 'bac_resolutions', 'requisitions', 'rpcis', 'rsmi_reports', 'inventory_reports', 'procurement_monitoring_reports', 'audit_logs', 'system_settings', 'stock_in'];
        foreach ($modules as $module) {
            foreach (['view', 'create', 'update', 'delete', 'approve'] as $action) {
                Permission::findOrCreate("{$module}.{$action}", 'web');
            }
        }
        $extras = ['requisitions.issue', 'requisitions.receive', 'purchase_requests.print', 'requisitions.print', 'request_for_quotations.print', 'abstracts_of_canvass.print', 'purchase_orders.print', 'inspection_acceptance_reports.print', 'bac_resolutions.print', 'rpcis.print', 'rpcis.preview', 'inspection_acceptance_reports.stock_in', 'inventory_reports.print', 'rsmi_reports.print'];
        foreach ($extras as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        // Admin has full permissions (mirrors production).
        Role::findOrCreate('admin', 'web')->syncPermissions(Permission::all()->pluck('name')->all());
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
     * Create an eligible BAC Resolution (via its ABC) so a Purchase Order
     * can be created from it.
     *
     * The ABC must originate from an existing, unconverted Request for
     * Quotation (which originates from an approved Purchase Request), so the
     * full PR -> RFQ -> ABC chain is built first.
     */
    private function makeBacSource(): \App\Models\BacResolution
    {
        $pr = \App\Models\PurchaseRequest::create([
            'date' => now()->subDays(4),
            'office_division' => 'Administrative Division',
            'name_of_project' => 'Supply of Office Materials',
            'purpose' => 'Replenish office materials',
            'source_of_fund' => 'GAA',
            'status' => \App\Models\PurchaseRequest::STATUS_APPROVED,
        ]);

        $rfq = \App\Models\RequestForQuotation::create([
            'purchase_request_id' => $pr->id,
            'date' => now()->subDays(3),
        ]);

        $abc = \App\Models\AbstractOfCanvass::create([
            'rfq_id' => $rfq->id,
            'supplier1_name' => 'ABC Trading',
            'recommendation' => 'ABC Trading',
            'date_of_opening' => now(),
        ]);

        return \App\Models\BacResolution::create([
            'abc_id' => $abc->id,
            'date' => now(),
        ]);
    }

    private function makePo(array $overrides = []): PurchaseOrder
    {
        return PurchaseOrder::create(array_merge([
            'bac_resolution_id' => $this->makeBacSource()->id,
            'supplier_name' => 'ABC Trading',
            'contact_number' => '09171234567',
            'address' => '123 Rizal St, Koronadal City',
            'tin' => '123456789',
            'date' => now()->toDateString(),
        ], $overrides));
    }

    // ─── Automatic sync on PO save ─────────────────────────────────

    public function test_po_save_creates_supplier_and_links_fk(): void
    {
        $po = $this->makePo();

        $supplier = Supplier::first();

        $this->assertNotNull($supplier);
        $this->assertSame('ABC Trading', $supplier->name);
        $this->assertSame('09171234567', $supplier->phone);
        $this->assertSame('123456789', $supplier->tin);
        $this->assertSame('123 Rizal St, Koronadal City', $supplier->address);
        $this->assertSame($supplier->id, $po->fresh()->supplier_id);
    }

    public function test_existing_supplier_is_reused_without_duplicate(): void
    {
        $existing = Supplier::create([
            'name' => 'ABC Trading',
            'phone' => '09170000000',
        ]);

        $po = $this->makePo(['supplier_name' => '  abc trading  ']);

        $this->assertSame(1, Supplier::count(), 'No duplicate supplier should be created');
        $this->assertSame($existing->id, $po->fresh()->supplier_id);
        $this->assertSame($existing->id, Supplier::first()->id);
        // Existing phone is the primary source — never overwritten.
        $this->assertSame('09170000000', $existing->fresh()->phone);
        // Missing fields are filled from PO data.
        $this->assertSame('123456789', $existing->fresh()->tin);
        $this->assertSame('123 Rizal St, Koronadal City', $existing->fresh()->address);
    }

    public function test_existing_supplier_data_is_never_overwritten(): void
    {
        $existing = Supplier::create([
            'name' => 'ABC Trading',
            'phone' => '09170000000',
            'tin' => '999888777',
            'address' => 'Existing Address',
        ]);

        $this->makePo([
            'contact_number' => '09171111111',
            'tin' => '123456789',
            'address' => 'PO Address',
        ]);

        $fresh = $existing->fresh();

        $this->assertSame(1, Supplier::count());
        $this->assertSame('09170000000', $fresh->phone);
        $this->assertSame('999888777', $fresh->tin);
        $this->assertSame('Existing Address', $fresh->address);
    }

    public function test_explicit_supplier_id_is_respected(): void
    {
        $other = Supplier::create(['name' => 'Other Trading']);

        $po = $this->makePo([
            'supplier_name' => 'ABC Trading',
            'supplier_id' => $other->id,
        ]);

        $this->assertSame(1, Supplier::count(), 'No new supplier should be created');
        $this->assertSame($other->id, $po->fresh()->supplier_id);
        $this->assertSame('Other Trading', $po->fresh()->supplier->name);
    }

    public function test_po_without_supplier_name_is_skipped(): void
    {
        $po = $this->makePo(['supplier_name' => null]);

        $this->assertSame(0, Supplier::count());
        $this->assertNull($po->fresh()->supplier_id);
    }

    public function test_supplier_tin_match_reuses_existing_record(): void
    {
        $byTin = Supplier::create(['name' => 'Different Legal Name', 'tin' => '123456789']);

        $po = $this->makePo(['supplier_name' => 'ABC Trading']);

        $this->assertSame(1, Supplier::count());
        $this->assertSame($byTin->id, $po->fresh()->supplier_id);
    }

    // ─── Backfill ──────────────────────────────────────────────────

    public function test_backfill_is_idempotent(): void
    {
        $po1 = $this->makePo();
        $po2 = $this->makePo(['supplier_name' => 'XYZ Supplies', 'tin' => '555444333']);
        $service = app(SupplierSyncService::class);

        // Both POs processed; each resolved to its own supplier.
        $this->assertSame(2, $service->backfillAll());
        $this->assertSame(2, Supplier::count());

        // Second run processes the same POs but creates nothing new and
        // preserves the links.
        $this->assertSame(2, $service->backfillAll());
        $this->assertSame(2, Supplier::count());
        $this->assertNotNull($po1->fresh()->supplier_id);
        $this->assertNotNull($po2->fresh()->supplier_id);
    }

    // ─── Supplier module display ───────────────────────────────────

    public function test_supplier_accessors_list_po_numbers_and_dates(): void
    {
        $po1 = $this->makePo(['date' => '2026-01-15']);
        $po2 = $this->makePo(['date' => '2026-02-20']);
        $supplier = Supplier::first();

        $this->assertSame(
            $po1->po_no.', '.$po2->po_no,
            $supplier->po_numbers
        );
        $this->assertSame('2026-01-15, 2026-02-20', $supplier->po_dates);
    }

    public function test_supplier_module_displays_po_numbers_and_dates(): void
    {
        $admin = $this->makeUser('admin');
        $po = $this->makePo();

        $this->actingAs($admin)
            ->get('/admin/suppliers')
            ->assertOk()
            ->assertSee($po->po_no, false)
            ->assertSee('ABC Trading', false);
    }
}
