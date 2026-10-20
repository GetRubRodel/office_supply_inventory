<?php

namespace Tests\Feature;

use App\Filament\Pages\RsmiReport;
use App\Filament\Resources\RequisitionResource\Pages\ViewRequisition;
use App\Models\Category;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MovingAverageCostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mirror RolePermissionSeeder's permission set so resource pages
        // (and their authorization checks) never hit Spatie's
        // PermissionDoesNotExist.
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
            'admin' => Permission::all()->pluck('name')->all(),
            'staff' => ['requisitions.view', 'requisitions.print'],
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

    private function makeCategory(): Category
    {
        return Category::create(['name' => 'Office Supplies']);
    }

    private function makeSupply(Category $category, int $qty = 0, float $unitCost = 0): Supply
    {
        return Supply::create([
            'stock_no' => 'STK-'.uniqid(),
            'category_id' => $category->id,
            'name' => 'Bond Paper',
            'unit' => 'ream',
            'reorder_level' => 5,
            'current_stock' => $qty,
            'unit_cost' => $unitCost,
            'average_unit_cost' => $unitCost,
            'total_cost' => $qty * $unitCost,
            'status' => 'active',
        ]);
    }

    private function makeApprovedRequisition(int $userId): Requisition
    {
        return Requisition::create([
            'user_id' => $userId,
            'division' => 'Administrative',
            'office' => 'Administrative Division',
            'entity_name' => 'CHR Region XII',
            'purpose' => 'Office supplies request',
            'status' => Requisition::STATUS_APPROVED,
        ]);
    }

    // ─── MAC on receipt (stock-in) ───────────────────────────────────

    public function test_receive_stock_blends_mac_from_existing_and_new_costs(): void
    {
        $supply = $this->makeSupply($this->makeCategory(), qty: 10, unitCost: 100);

        // 10 existing @ 100 blended with 10 new @ 120 → MAC 110
        $supply->receiveStock(10, 120, 'PO-2026-0001', '2026-08-09');

        $supply->refresh();

        $this->assertEquals(20, $supply->current_stock);
        $this->assertEquals(110.0, (float) $supply->unit_cost);
        $this->assertEquals(110.0, (float) $supply->average_unit_cost);
        $this->assertEqualsWithDelta(2200.0, (float) $supply->total_cost, 0.01);
        $this->assertEquals('PO-2026-0001', $supply->latest_reference_number);
        $this->assertEquals('2026-08-09', $supply->last_received_date->format('Y-m-d'));
    }

    public function test_receive_stock_on_empty_inventory_sets_mac_to_received_cost(): void
    {
        $supply = $this->makeSupply($this->makeCategory(), qty: 0, unitCost: 0);

        $supply->receiveStock(8, 75);

        $supply->refresh();

        $this->assertEquals(8, $supply->current_stock);
        $this->assertEquals(75.0, (float) $supply->unit_cost);
        $this->assertEqualsWithDelta(600.0, (float) $supply->total_cost, 0.01);
    }

    public function test_receive_stock_rejects_non_positive_quantity(): void
    {
        $supply = $this->makeSupply($this->makeCategory(), qty: 10, unitCost: 100);

        $this->expectException(\InvalidArgumentException::class);
        $supply->receiveStock(0, 100);
    }

    // ─── MAC on issue (stock-out) ────────────────────────────────────

    public function test_issue_stock_keeps_mac_and_reduces_inventory_value(): void
    {
        $supply = $this->makeSupply($this->makeCategory(), qty: 20, unitCost: 110);

        $supply->issueStock(5, 'RIS-2026-0001');

        $supply->refresh();

        $this->assertEquals(15, $supply->current_stock);
        $this->assertEquals(110.0, (float) $supply->unit_cost); // MAC unchanged
        $this->assertEqualsWithDelta(1650.0, (float) $supply->total_cost, 0.01);
        $this->assertEquals('RIS-2026-0001', $supply->reference_number);
    }

    public function test_issue_stock_prevents_negative_balance(): void
    {
        $supply = $this->makeSupply($this->makeCategory(), qty: 5, unitCost: 100);

        try {
            $supply->issueStock(6, 'RIS-2026-0001');
            $this->fail('Expected a RuntimeException for insufficient stock.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Insufficient stock', $e->getMessage());
        }

        // Balance untouched after the failed issue.
        $supply->refresh();
        $this->assertEquals(5, $supply->current_stock);
        $this->assertEqualsWithDelta(500.0, (float) $supply->total_cost, 0.01);
    }

    // ─── Issued-cost resolution (used by RSMI) ───────────────────────

    public function test_issued_unit_cost_resolution_order(): void
    {
        $supply = $this->makeSupply($this->makeCategory(), qty: 10, unitCost: 60);
        $supply->update(['unit_price' => 90]);

        $ris = $this->makeApprovedRequisition($this->makeUser('staff')->id);
        $item = RequisitionItem::create([
            'requisition_id' => $ris->id,
            'supply_id' => $supply->id,
            'quantity_requested' => 2,
            'price' => 50,
        ]);

        // 1. MAC snapshot at issue wins when present.
        $item->update(['issued_unit_cost' => 55]);
        $this->assertEquals(55.0, $item->unit_cost_for_issuance);

        // 2. Falls back to the request-time price snapshot.
        $item->update(['issued_unit_cost' => null]);
        $this->assertEquals(50.0, $item->unit_cost_for_issuance);

        // 3. Falls back to the supply's current MAC.
        $item->update(['issued_unit_cost' => null, 'price' => null]);
        $this->assertEquals(60.0, $item->unit_cost_for_issuance);

        // 4. Last resort: the supply's standard unit price.
        $supply->update(['unit_cost' => 0]);
        $item->unsetRelation('supply');
        $this->assertEquals(90.0, $item->unit_cost_for_issuance);
    }

    // ─── Full issuance workflow ──────────────────────────────────────

    public function test_ris_issue_deducts_stock_snapshots_mac_and_updates_value(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');

        $supply = $this->makeSupply($this->makeCategory(), qty: 10, unitCost: 100);
        $supply->receiveStock(10, 120); // MAC 110, qty 20

        $ris = $this->makeApprovedRequisition($staff->id);
        RequisitionItem::create([
            'requisition_id' => $ris->id,
            'supply_id' => $supply->id,
            'unit' => 'ream',
            'description' => 'Bond Paper',
            'quantity_requested' => 5,
            'quantity_issued' => 5,
            'price' => 100,
        ]);

        $this->actingAs($admin);

        Livewire::test(ViewRequisition::class, ['record' => $ris->getRouteKey()])
            ->callAction('issue');

        $item = $ris->items()->first()->refresh();
        $supply->refresh();

        // MAC at issue time snapshotted onto the line item.
        $this->assertEquals(110.0, (float) $item->issued_unit_cost);

        // Stock deducted, MAC unchanged, inventory value reduced.
        $this->assertEquals(15, $supply->current_stock);
        $this->assertEquals(110.0, (float) $supply->unit_cost);
        $this->assertEqualsWithDelta(1650.0, (float) $supply->total_cost, 0.01);

        // RIS moved through the pipeline.
        $this->assertEquals(Requisition::STATUS_PENDING_RECEIPT, $ris->refresh()->status);

        // Audit trail preserved: who issued and when.
        $this->assertNotNull($ris->issued_by_name);
        $this->assertNotNull($ris->issued_by_date);
    }

    public function test_rsmi_values_issue_at_mac_snapshot_even_after_later_receipts(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');

        $supply = $this->makeSupply($this->makeCategory(), qty: 10, unitCost: 100);
        $supply->receiveStock(10, 120); // MAC 110, qty 20

        $ris = $this->makeApprovedRequisition($staff->id);
        RequisitionItem::create([
            'requisition_id' => $ris->id,
            'supply_id' => $supply->id,
            'unit' => 'ream',
            'description' => 'Bond Paper',
            'quantity_requested' => 5,
            'quantity_issued' => 5,
            'price' => 100,
        ]);

        $this->actingAs($admin);
        Livewire::test(ViewRequisition::class, ['record' => $ris->getRouteKey()])
            ->callAction('issue');

        // A later receipt at a different cost moves the MAC forward…
        $supply->refresh()->receiveStock(5, 200);
        $this->assertEquals(132.5, (float) $supply->refresh()->unit_cost);

        // …but the historical RSMI entry keeps the MAC that was in effect
        // when the items were issued (transaction history preserved).
        $page = new RsmiReport();
        $page->filters = [];
        $recap = $page->getRecapitulation();

        $this->assertCount(1, $recap);
        $this->assertEquals(110.0, (float) $recap[0]['unit_cost']);
        $this->assertEquals(5, $recap[0]['quantity']);
    }
}
