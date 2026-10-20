<?php

namespace Tests\Feature;

use App\Filament\Resources\InspectionAcceptanceResource\Pages\CreateInspectionAcceptanceReport;
use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\Category;
use App\Models\InspectionAcceptanceReport;
use App\Models\IarItem;
use App\Models\PoItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\StockInRecord;
use App\Models\Supply;
use App\Models\User;
use App\Services\StockInService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StockInDescriptionRecoveryTest extends TestCase
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
     * Build the PR -> RFQ -> ABC -> BAC -> PO chain with one PO item.
     */
    private function makePoWithItem(): PurchaseOrder
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
        ]);

        $bac = BacResolution::create([
            'abc_id' => $abc->id,
            'date' => '2026-08-08',
        ]);

        $po = PurchaseOrder::create([
            'bac_resolution_id' => $bac->id,
            'abc_id' => $abc->id,
            'date' => '2026-08-10',
            'supplier_name' => 'Test Supplier',
            'total_amount' => 155.00,
        ]);

        PoItem::create([
            'po_id' => $po->id,
            'stock_no' => 'STK-TEST-001',
            'unit' => 'Box',
            'description' => 'Staple wire #35',
            'quantity' => 5,
            'unit_cost' => 31.00,
            'amount' => 155.00,
        ]);

        return $po;
    }

    public function test_stock_in_recovers_missing_description_from_source_po(): void
    {
        $this->makeUser('admin');
        $po = $this->makePoWithItem();
        $category = Category::create(['name' => 'Office Supplies']);

        // Pre-existing supply matching the PO item description (avoids the
        // MySQL-only SUBSTRING_INDEX stock-no generator in SQLite tests).
        $supply = Supply::create([
            'name' => 'Staple wire #35',
            'unit' => 'Box',
            'stock_no' => 'STK-TEST-001',
            'category_id' => $category->id,
            'current_stock' => 0,
            'unit_cost' => 0,
            'average_unit_cost' => 0,
            'total_cost' => 0,
            'reorder_level' => 0,
            'status' => 'active',
        ]);

        // Simulate a broken IAR item: description/unit/quantity/unit_cost were
        // never persisted (NULL / defaults), only the category was saved.
        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
            'supplier_name' => 'Test Supplier',
        ]);

        $iarItem = IarItem::create([
            'iar_id' => $iar->id,
            'stock_no' => null,
            'unit' => null,
            'description' => null,
            'quantity' => 1,
            'unit_cost' => null,
            'category_id' => $category->id,
        ]);

        $result = app(StockInService::class)->process($iar->fresh());

        $this->assertTrue($result['success']);

        // The description (and other missing fields) must be recovered from the PO.
        $iarItem->refresh();
        $this->assertSame('Staple wire #35', $iarItem->description);
        $this->assertSame('Box', $iarItem->unit);
        $this->assertSame(5, $iarItem->quantity);
        $this->assertSame('31.00', (string) $iarItem->unit_cost);

        // The stock-in record must carry the recovered quantity.
        $record = StockInRecord::where('iar_id', $iar->id)->first();
        $this->assertNotNull($record);
        $this->assertSame(5, $record->quantity_received);
        $this->assertSame('31.00', (string) $record->unit_cost);

        // The existing supply must have been reused and its stock updated.
        $supply->refresh();
        $this->assertSame(5, $supply->current_stock);

        // The IAR must be marked as stocked in.
        $this->assertTrue($iar->fresh()->isStockedIn());
    }

    public function test_stock_in_fails_with_clear_message_when_description_is_genuinely_missing(): void
    {
        $this->makeUser('admin');
        $po = $this->makePoWithItem();
        $category = Category::create(['name' => 'Office Supplies']);

        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
            'supplier_name' => 'Test Supplier',
        ]);

        // Broken item with NO matching PO item (PO has 1 item, we add 2 IAR items).
        IarItem::create([
            'iar_id' => $iar->id,
            'stock_no' => null,
            'unit' => null,
            'description' => null,
            'quantity' => 1,
            'unit_cost' => null,
            'category_id' => $category->id,
        ]);
        $unmatchedItem = IarItem::create([
            'iar_id' => $iar->id,
            'stock_no' => null,
            'unit' => null,
            'description' => null,
            'quantity' => 1,
            'unit_cost' => null,
            'category_id' => $category->id,
        ]);

        try {
            app(StockInService::class)->process($iar->fresh());
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();
            $this->assertStringContainsString("row {$unmatchedItem->id}", $message);
            $this->assertStringContainsString('missing a description', $message);
        }

        // No partial stock-in records may exist.
        $this->assertSame(0, StockInRecord::count());
        $this->assertFalse($iar->fresh()->isStockedIn());
    }

    public function test_iar_form_persists_disabled_item_fields(): void
    {
        $admin = $this->makeUser('admin');
        $po = $this->makePoWithItem();
        $category = Category::create(['name' => 'Office Supplies']);

        $this->actingAs($admin);

        $component = Livewire::test(CreateInspectionAcceptanceReport::class)
            ->fillForm([
                'po_id' => $po->id,
            ]);

        // The po_id afterStateUpdated populates the items repeater from the PO.
        $items = $component->get('data.items');
        $this->assertNotEmpty($items, 'Items should be populated from the PO');
        $uuid = array_key_first($items);
        $items[$uuid]['category_id'] = $category->id;

        $component
            ->fillForm([
                'date' => '2026-08-18',
                'requisitioning_office_dept' => 'ADMINISTRATIVE DIVISION',
                'inspector_name' => 'ANA FE B. GALANTO',
                'inspector_designation' => 'Admin. Assistant II',
                'inspection_date' => '2026-08-18',
                'acceptor_name' => 'RHODELIA J. MANDOLADO',
                'acceptor_designation' => 'Admin. Officer IV',
                'acceptance_date' => '2026-08-18',
                'items' => $items,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $iar = InspectionAcceptanceReport::first();
        $this->assertNotNull($iar, 'IAR should be created');

        $iarItem = $iar->items()->first();
        $this->assertNotNull($iarItem, 'IAR item should be created');
        $this->assertSame('Staple wire #35', $iarItem->description);
        $this->assertSame('Box', $iarItem->unit);
        $this->assertSame(5, $iarItem->quantity);
        $this->assertSame('31.00', (string) $iarItem->unit_cost);
        $this->assertSame('STK-TEST-001', $iarItem->stock_no);
    }
}