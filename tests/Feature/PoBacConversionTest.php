<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\AbcItem;
use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * End-to-end verification of the Purchase Order ← BAC Resolution conversion
 * workflow:
 *
 *   1. A Purchase Order must be created from an eligible (unconverted)
 *      BAC Resolution.
 *   2. On creation, the PO auto-hydrates the source reference data
 *      (resolution number snapshot and the derived PO → ABC relationship).
 *   3. The source BAC Resolution becomes ineligible for further conversions.
 *   4. Duplicate conversion is blocked at the model and database levels.
 */
class PoBacConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The User model does not implement FilamentUser, so Filament's
        // panel-auth middleware only permits access when APP_ENV=local.
        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

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

        $rolePermissions = [
            'admin' => ['bac_resolutions.view', 'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.update', 'purchase_orders.delete'],
            'supply_officer' => ['bac_resolutions.view', 'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.update', 'purchase_orders.delete'],
        ];
        foreach ($rolePermissions as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'approved',
        ]);
        $user->assignRole('admin');

        return $user;
    }

    /**
     * Create an ABC and convert it into a BAC Resolution so it can be used
     * as the source document for a Purchase Order.
     *
     * The ABC must originate from an existing, unconverted Request for
     * Quotation (which originates from an approved Purchase Request), so the
     * full PR -> RFQ -> ABC chain is built first.
     */
    private function makeBac(): BacResolution
    {
        $pr = PurchaseRequest::create([
            'date' => now()->subDays(4),
            'office_division' => 'Administrative Division',
            'name_of_project' => 'Supply of Office Materials',
            'purpose' => 'Replenish office materials',
            'source_of_fund' => 'GAA',
            'status' => PurchaseRequest::STATUS_APPROVED,
        ]);

        $rfq = RequestForQuotation::create([
            'purchase_request_id' => $pr->id,
            'date' => now()->subDays(3),
        ]);

        $abc = AbstractOfCanvass::create([
            'rfq_id' => $rfq->id,
            'supplier1_name' => 'Test Supplier Co.',
            'supplier2_name' => 'Other Supplier Inc.',
            'supplier3_name' => 'Third Supplier LLC',
            'recommendation' => 'Test Supplier Co.',
            'date_of_opening' => now(),
            'status' => 'draft',
        ]);

        AbcItem::create([
            'abc_id' => $abc->id,
            'item_number' => 1,
            'description' => 'Bond Paper',
            'unit' => 'Ream',
            'quantity' => 10,
            'supplier1_price' => 220.00,
            'supplier2_price' => 230.00,
            'supplier3_price' => 240.00,
        ]);

        return BacResolution::create([
            'abc_id' => $abc->id,
            'title' => 'Resolution title',
            'date' => now(),
        ]);
    }

    private function makePoData(BacResolution $bac): array
    {
        return [
            'bac_resolution_id' => $bac->id,
            'supplier_name' => 'Test Supplier Co.',
            'address' => 'Koronadal City',
            'tin' => '123456789',
            'date' => now(),
            'mode_of_procurement' => 'Public Bidding',
        ];
    }

    public function test_po_creation_from_bac_hydrates_source_data(): void
    {
        $bac = $this->makeBac();

        $po = PurchaseOrder::create($this->makePoData($bac));

        // Auto-generated P.O. number in PO-YYYY-NNNN format.
        $this->assertMatchesRegularExpression('/^PO-\d{4}-\d{4}$/', $po->po_no);

        // Snapshot hydrated from the source BAC Resolution.
        $this->assertEquals($bac->resolution_no, $po->resolution_no);
        $this->assertTrue($po->bacResolution()->is($bac));

        // The legacy PO → ABC relationship is preserved via the source
        // BAC Resolution's own ABC (reports traverse PO → ABC → RFQ → PR).
        $this->assertEquals($bac->abc_id, $po->abc_id);
    }

    public function test_converted_bac_is_no_longer_eligible(): void
    {
        $bac = $this->makeBac();

        $this->assertFalse($bac->purchaseOrders()->exists());
        $this->assertTrue(PurchaseOrderResource::hasEligibleBacResolution());

        PurchaseOrder::create($this->makePoData($bac));

        $bac->refresh();
        $this->assertTrue($bac->purchaseOrders()->exists());
        $this->assertFalse(PurchaseOrderResource::hasEligibleBacResolution());
    }

    public function test_second_po_from_same_bac_is_rejected(): void
    {
        $bac = $this->makeBac();

        PurchaseOrder::create($this->makePoData($bac));

        $this->expectException(ValidationException::class);

        PurchaseOrder::create($this->makePoData($bac));
    }

    public function test_po_without_bac_source_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        PurchaseOrder::create([
            'supplier_name' => 'Test Supplier Co.',
            'date' => now(),
        ]);
    }

    public function test_create_page_redirects_when_no_eligible_bac(): void
    {
        $user = $this->makeAdmin();

        // No BAC Resolutions at all -> mount guard redirects back to the list.
        $this->actingAs($user)
            ->get('/admin/purchase-orders/create')
            ->assertRedirect('/admin/purchase-orders');
    }

    public function test_create_page_loads_when_eligible_bac_exists(): void
    {
        $this->makeBac();

        $this->actingAs($this->makeAdmin())
            ->get('/admin/purchase-orders/create')
            ->assertOk();
    }

    public function test_create_page_redirects_after_bac_converted(): void
    {
        $bac = $this->makeBac();
        PurchaseOrder::create($this->makePoData($bac));

        // The only BAC Resolution is now converted -> no eligible sources remain.
        $this->actingAs($this->makeAdmin())
            ->get('/admin/purchase-orders/create')
            ->assertRedirect('/admin/purchase-orders');
    }

    public function test_po_list_visible_for_permitted_user(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/purchase-orders')
            ->assertOk();
    }

    public function test_po_list_hides_create_button_and_shows_notice_when_no_eligible_bac(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/purchase-orders')
            ->assertOk()
            ->assertDontSee('New purchase order')
            // Marker class lives only in the rendered banner markup (never
            // in Livewire's serialized snapshot), so this proves the notice
            // is visibly displayed.
            ->assertSee('fi-procurement-blocked-notice')
            ->assertSee(PurchaseOrder::NO_ELIGIBLE_BAC_MESSAGE);
    }

    public function test_po_list_shows_create_button_when_eligible_bac_exists(): void
    {
        $this->makeBac();

        $this->actingAs($this->makeAdmin())
            ->get('/admin/purchase-orders')
            ->assertOk()
            ->assertSee('New purchase order')
            ->assertDontSee('fi-procurement-blocked-notice');
    }
}
