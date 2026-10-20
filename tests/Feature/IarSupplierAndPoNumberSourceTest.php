<?php

namespace Tests\Feature;

use App\Filament\Resources\InspectionAcceptanceResource\Pages\ListInspectionAcceptanceReports;
use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The Supplier and P.O. No. columns must always be derived from the source
 * Purchase Order — never hard-coded, never blank when the relationship
 * exists, and never fatal when it does not.
 */
class IarSupplierAndPoNumberSourceTest extends TestCase
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
        foreach (['requisitions.issue', 'requisitions.receive', 'purchase_requests.print', 'requisitions.print', 'request_for_quotations.print', 'abstracts_of_canvass.print', 'purchase_orders.print', 'inspection_acceptance_reports.print', 'bac_resolutions.print', 'rpcis.print', 'rpcis.preview', 'inspection_acceptance_reports.stock_in', 'inventory_reports.print', 'rsmi_reports.print'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        Role::findOrCreate('admin', 'web')->syncPermissions(Permission::all()->pluck('name')->all());
        Role::findOrCreate('staff', 'web');
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
     * Build the full PR -> RFQ -> ABC -> BAC -> PO chain, which is what the
     * PO (and therefore the IAR) sources its supplier name from.
     */
    private function makeChain(string $supplierName = 'Kristan Educational Supply'): PurchaseOrder
    {
        $owner = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
        ]);

        $pr = PurchaseRequest::create([
            'user_id' => $owner->id,
            'date' => '2026-08-01',
            'office_division' => 'Administrative Division',
            'name_of_project' => 'Supply of Office Materials',
            'purpose' => 'Replenish office materials',
            'source_of_fund' => 'GAA',
            'status' => PurchaseRequest::STATUS_APPROVED,
        ]);

        $rfq = RequestForQuotation::create([
            'purchase_request_id' => $pr->id,
            'date' => '2026-08-05',
        ]);

        $abc = AbstractOfCanvass::create([
            'rfq_id' => $rfq->id,
            'supplier1_name' => $supplierName,
            'supplier2_name' => 'Other Supplier Inc.',
            'supplier3_name' => 'Third Supplier LLC',
            'recommendation' => $supplierName,
            'date_of_advertisement' => now(),
            'date_of_opening' => now(),
        ]);

        $bac = BacResolution::create([
            'abc_id' => $abc->id,
            'title' => 'Resolution title',
            'date' => '2026-08-08',
        ]);

        return PurchaseOrder::create([
            'bac_resolution_id' => $bac->id,
            'supplier_name' => $supplierName,
            'address' => 'Koronadal City',
            'tin' => '123456789',
            'date' => '2026-08-10',
            'mode_of_procurement' => 'Public Bidding',
        ]);
    }

    /**
     * Insert an Inspection and Acceptance Report straight through the query
     * builder so the model-level source guard never runs.
     *
     * Foreign key enforcement is suspended for the insert so a deliberately
     * dangling po_id can be simulated. The schema keeps
     * inspection_acceptance_reports.po_id nullOnDelete, so an orphaned row can
     * only reach production through a dump taken without foreign keys — the
     * accessors and the table must survive that anyway.
     */
    private function insertRawIar(array $attributes): InspectionAcceptanceReport
    {
        $connection = DB::connection();

        if ($connection->getDriverName() === 'sqlite') {
            // RefreshDatabase runs each test inside a transaction, and SQLite
            // ignores `PRAGMA foreign_keys = OFF` inside one. Deferring the
            // check to commit time works, and the transaction only ever rolls
            // back.
            $connection->statement('PRAGMA defer_foreign_keys = ON');
        } else {
            Schema::disableForeignKeyConstraints();
        }

        try {
            InspectionAcceptanceReport::query()->insert(array_merge([
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ], $attributes));
        } finally {
            if ($connection->getDriverName() === 'sqlite') {
                $connection->statement('PRAGMA defer_foreign_keys = OFF');
            } else {
                Schema::enableForeignKeyConstraints();
            }
        }

        return InspectionAcceptanceReport::query()
            ->where('iar_no', $attributes['iar_no'])
            ->firstOrFail();
    }

    public function test_iar_relationship_to_purchase_order_is_verified(): void
    {
        $po = $this->makeChain();
        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
        ]);

        $this->assertTrue($iar->relationLoaded('po') || $iar->po()->is($po));
        $this->assertSame($po->id, $iar->po->id, 'The IAR must preserve its source Purchase Order relationship.');
    }

    public function test_iar_supplier_relationship_resolves_through_purchase_order(): void
    {
        $po = $this->makeChain('Kristan Educational Supply');

        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
        ]);

        // IAR -> PO -> Supplier
        $this->assertNotNull(
            $po->supplier_id,
            'The PO should have been synced to a Supplier record on save.',
        );
        $this->assertTrue(
            $iar->supplier()->is($po->supplier),
            'The IAR supplier relation must resolve through the source Purchase Order.',
        );
    }

    public function test_iar_hydrates_supplier_name_and_po_no_from_purchase_order_on_create(): void
    {
        $po = $this->makeChain('Kristan Educational Supply');

        // Deliberately create WITHOUT passing supplier_name / po_no, the way a
        // non-Filament entry point would. The values must still be captured
        // from the Purchase Order.
        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
        ]);

        $this->assertSame('Kristan Educational Supply', $iar->supplier_name);
        $this->assertSame($po->po_no, $iar->po_no);
        $this->assertNotNull($iar->getRawOriginal('supplier_name'), 'The stored snapshot must be persisted.');
        $this->assertNotNull($iar->getRawOriginal('po_no'), 'The stored snapshot must be persisted.');
    }

    public function test_iar_accessors_ignore_stale_stored_values(): void
    {
        $po = $this->makeChain('Kristan Educational Supply');

        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
            'supplier_name' => 'HARD-CODED / STALE VALUE',
            'po_no' => 'PO-STALE-0000',
        ]);

        $this->assertSame('Kristan Educational Supply', $iar->supplier_name);
        $this->assertSame($po->po_no, $iar->po_no);
    }

    public function test_iar_accessors_recover_null_snapshots_from_purchase_order(): void
    {
        $po = $this->makeChain('Kristan Educational Supply');

        // Reproduce the historical bug: a valid po_id whose supplier_name and
        // po_no snapshots were never written.
        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
        ]);
        InspectionAcceptanceReport::query()->where('id', $iar->id)->update([
            'supplier_name' => null,
            'po_no' => null,
        ]);

        $iar = InspectionAcceptanceReport::findOrFail($iar->id);

        $this->assertSame('Kristan Educational Supply', $iar->supplier_name);
        $this->assertSame($po->po_no, $iar->po_no);
    }

    public function test_iar_falls_back_to_stored_snapshot_when_po_has_no_supplier(): void
    {
        // Legacy PO with no BAC Resolution and no supplier name, inserted via
        // the query builder to bypass the model-level source guard.
        PurchaseOrder::query()->insert([
            'po_no' => 'PO-2026-8888',
            'date' => '2026-08-10',
            'supplier_name' => 'Legacy Supplier',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $po = PurchaseOrder::where('po_no', 'PO-2026-8888')->firstOrFail();

        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
            'supplier_name' => 'Legacy Supplier',
            'po_no' => 'PO-2026-8888',
        ]);

        $this->assertSame('Legacy Supplier', $iar->supplier_name);
        $this->assertSame('PO-2026-8888', $iar->po_no);
    }

    public function test_iar_handles_missing_purchase_order_without_erroring(): void
    {
        // An IAR whose PO no longer exists (bypasses the create-time guard).
        $iar = $this->insertRawIar([
            'iar_no' => 'IAR-2026-9999',
            'po_id' => 999999,
            'supplier_name' => null,
            'po_no' => null,
            'date' => '2026-08-18',
        ]);

        $this->assertNull($iar->po, 'The missing Purchase Order must resolve to null, not throw.');
        $this->assertNull($iar->supplier_name);
        $this->assertNull($iar->po_no);
        $this->assertNull($iar->supplier);
    }

    public function test_iar_table_displays_supplier_name_and_po_no(): void
    {
        $admin = $this->makeUser('admin');
        $po = $this->makeChain('Kristan Educational Supply');

        $this->insertRawIar([
            'iar_no' => 'IAR-2026-4242',
            'po_id' => $po->id,
            'supplier_name' => null,
            'po_no' => null,
            'date' => '2026-08-18',
        ]);

        Livewire::actingAs($admin)
            ->test(ListInspectionAcceptanceReports::class)
            ->assertSuccessful()
            ->assertSee('Kristan Educational Supply')
            ->assertSee($po->po_no);
    }

    public function test_iar_table_renders_placeholder_when_relationship_is_missing(): void
    {
        $admin = $this->makeUser('admin');

        $this->insertRawIar([
            'iar_no' => 'IAR-2026-7777',
            'po_id' => 999999,
            'supplier_name' => null,
            'po_no' => null,
            'date' => '2026-08-18',
        ]);

        Livewire::actingAs($admin)
            ->test(ListInspectionAcceptanceReports::class)
            ->assertSuccessful()
            ->assertSee(InspectionAcceptanceReport::MISSING_VALUE_PLACEHOLDER);
    }

    public function test_iar_print_view_shows_supplier_name_and_po_no_from_purchase_order(): void
    {
        $admin = $this->makeUser('admin');
        $po = $this->makeChain('Kristan Educational Supply');

        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
        ]);
        InspectionAcceptanceReport::query()->where('id', $iar->id)->update([
            'supplier_name' => null,
            'po_no' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('iar.print', $iar))
            ->assertOk()
            ->assertSee('Kristan Educational Supply')
            ->assertSee($po->po_no);
    }

    public function test_switching_po_rehydrates_supplier_name_and_po_no(): void
    {
        $first = $this->makeChain('Kristan Educational Supply');
        $iar = InspectionAcceptanceReport::create([
            'po_id' => $first->id,
            'date' => '2026-08-18',
        ]);

        $this->assertSame('Kristan Educational Supply', $iar->supplier_name);

        $second = $this->makeChain('KCC Mall of Marbel');

        $iar->po_id = $second->id;
        $iar->save();

        $this->assertSame('KCC Mall of Marbel', $iar->supplier_name);
        $this->assertSame($second->po_no, $iar->po_no);
    }

    public function test_supplier_record_name_is_not_used_as_display_source(): void
    {
        // Guards the deliberate decision documented on InspectionAcceptanceReport:
        // SupplierSyncService keeps a stale PO -> Supplier link when the PO's
        // supplier name drifts, so suppliers.name is not authoritative here.
        $po = $this->makeChain('Kristan Educational Supply');

        $stale = Supplier::create([
            'name' => 'Starbright Home Depot INC.',
            'tin' => '999999999',
        ]);
        PurchaseOrder::query()->where('id', $po->id)->update(['supplier_id' => $stale->id]);

        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
        ]);

        $this->assertSame('Kristan Educational Supply', $iar->supplier_name);
        $this->assertTrue($iar->supplier->is($stale), 'The relation itself still points at the linked Supplier record.');
    }
}
