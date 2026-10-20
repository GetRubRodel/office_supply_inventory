<?php

namespace Tests\Feature;

use App\Filament\Resources\BacResolutionResource\Pages\CreateBacResolution;
use App\Models\AbcItem;
use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The BAC Resolution's Winning Supplier, unit costs, total costs and total
 * amount must all come from the same ABC record — the supplier with the
 * lowest valid (complete) total bid. Values are sourced live from the ABC's
 * bid data, never from hardcoded names/prices or stale snapshots.
 */
class BacWinningSupplierTest extends TestCase
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
        $extras = ['requisitions.issue', 'requisitions.receive', 'purchase_requests.print', 'requisitions.print', 'request_for_quotations.print', 'abstracts_of_canvass.print', 'purchase_orders.print', 'inspection_acceptance_reports.print', 'bac_resolutions.print', 'rpcis.print', 'rpcis.preview', 'inspection_acceptance_reports.stock_in', 'inventory_reports.print', 'rsmi_reports.print'];
        foreach ($extras as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $rolePermissions = [
            'admin' => ['abstracts_of_canvass.view', 'bac_resolutions.view', 'bac_resolutions.create', 'bac_resolutions.update', 'bac_resolutions.delete'],
            'supply_officer' => ['abstracts_of_canvass.view', 'bac_resolutions.view', 'bac_resolutions.create', 'bac_resolutions.update', 'bac_resolutions.delete'],
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
     * Build the PR -> RFQ -> ABC chain with the given item specs. No
     * recommendation / winning supplier is stored unless explicitly passed,
     * so the winner must be resolved from the live bid data.
     */
    private function makeAbc(array $itemSpecs, array $abcOverrides = []): AbstractOfCanvass
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

        $abc = AbstractOfCanvass::create(array_merge([
            'rfq_id' => $rfq->id,
            'supplier1_name' => 'Test Supplier Co.',
            'supplier2_name' => 'Other Supplier Inc.',
            'supplier3_name' => 'Third Supplier LLC',
            'date_of_advertisement' => now(),
            'date_of_opening' => now(),
            'status' => 'draft',
        ], $abcOverrides));

        foreach ($itemSpecs as $spec) {
            AbcItem::create(['abc_id' => $abc->id] + $spec);
        }

        return $abc->fresh();
    }

    /**
     * Two items where supplier 2 has the lowest total bid:
     *   item 1: 10 × (100 / 80 / 90)   → s1: 1000, s2: 800, s3: 900
     *   item 2:  5 × ( 50 / 40 / 45)   → s1:  250, s2: 200, s3: 225
     *   totals:                        → s1: 1250, s2: 1000, s3: 1125
     */
    private function makeAbcWithSupplier2Winning(): AbstractOfCanvass
    {
        return $this->makeAbc([
            ['item_number' => 1, 'description' => 'Bond Paper', 'unit' => 'Ream', 'quantity' => 10, 'supplier1_price' => 100.00, 'supplier2_price' => 80.00, 'supplier3_price' => 90.00],
            ['item_number' => 2, 'description' => 'Ballpen', 'unit' => 'Piece', 'quantity' => 5, 'supplier1_price' => 50.00, 'supplier2_price' => 40.00, 'supplier3_price' => 45.00],
        ]);
    }

    public function test_winning_supplier_is_lowest_total_bid_without_stored_recommendation(): void
    {
        $abc = $this->makeAbcWithSupplier2Winning();

        $this->assertSame(1, $abc->winningSupplierIndex());
        $this->assertSame('Other Supplier Inc.', $abc->winningSupplierName());
        $this->assertSame('supplier2_price', $abc->winningPriceColumn());
    }

    public function test_winning_supplier_ignores_stale_recommendation(): void
    {
        // The stored recommendation points at supplier 1, but supplier 2 has
        // the lowest total bid — the live bid data must win.
        $abc = $this->makeAbcWithSupplier2Winning();
        $abc->update(['recommendation' => 'Test Supplier Co.']);

        $this->assertSame(1, $abc->winningSupplierIndex());
        $this->assertSame('Other Supplier Inc.', $abc->winningSupplierName());
    }

    public function test_incomplete_bid_never_wins(): void
    {
        // Supplier 1 is missing a price on item 2 — its partial total would
        // be artificially low, so it must be excluded from the evaluation.
        $abc = $this->makeAbc([
            ['item_number' => 1, 'description' => 'A', 'unit' => 'pc', 'quantity' => 10, 'supplier1_price' => 10.00, 'supplier2_price' => 100.00, 'supplier3_price' => 110.00],
            ['item_number' => 2, 'description' => 'B', 'unit' => 'pc', 'quantity' => 5, 'supplier1_price' => null, 'supplier2_price' => 90.00, 'supplier3_price' => 95.00],
        ]);

        $this->assertSame(1, $abc->winningSupplierIndex());
        $this->assertSame('Other Supplier Inc.', $abc->winningSupplierName());
    }

    public function test_bac_snapshot_uses_winning_supplier_prices_and_totals(): void
    {
        $abc = $this->makeAbcWithSupplier2Winning();

        $bac = BacResolution::create(['abc_id' => $abc->id, 'date' => now()]);

        $this->assertSame('Other Supplier Inc.', $bac->supplier_name);

        $snapshot = $bac->items_snapshot;
        $this->assertCount(2, $snapshot);
        $this->assertEquals(80.00, (float) $snapshot[0]['unit_cost']);
        $this->assertEquals(800.00, (float) $snapshot[0]['total']);
        $this->assertEquals(40.00, (float) $snapshot[1]['unit_cost']);
        $this->assertEquals(200.00, (float) $snapshot[1]['total']);

        // Total = (10 × 80) + (5 × 40) = 800 + 200 = 1000.00
        $this->assertEquals(1000.00, $bac->getTotalAmount());
    }

    public function test_bac_snapshot_reflects_abc_bid_changes(): void
    {
        $abc = $this->makeAbcWithSupplier2Winning();
        $bac = BacResolution::create(['abc_id' => $abc->id, 'date' => now()]);

        $this->assertSame('Other Supplier Inc.', $bac->supplier_name);

        // Re-price so supplier 3 now has the lowest total bid.
        $abc->items()->update(['supplier2_price' => 200.00, 'supplier3_price' => 30.00]);

        $fresh = $bac->fresh();
        $this->assertSame('Third Supplier LLC', $fresh->supplier_name);
        $this->assertEquals(30.00, (float) $fresh->items_snapshot[0]['unit_cost']);
        $this->assertEquals(300.00, (float) $fresh->items_snapshot[0]['total']);
        // Total = (10 × 30) + (5 × 30) = 300 + 150 = 450.00
        $this->assertEquals(450.00, $fresh->getTotalAmount());
    }

    public function test_bac_form_displays_winning_supplier_and_items(): void
    {
        $admin = $this->makeAdmin();
        $abc = $this->makeAbcWithSupplier2Winning();

        $this->actingAs($admin);

        Livewire::test(CreateBacResolution::class)
            ->set('data.abc_id', $abc->id)
            ->assertSet('data.supplier_name', 'Other Supplier Inc.')
            ->assertSee('Other Supplier Inc.')
            ->assertSee('80.00')      // winning unit cost on item 1
            ->assertSee('1,000.00');  // total amount
    }

    public function test_bac_print_omits_winning_supplier_and_items(): void
    {
        $admin = $this->makeAdmin();
        $abc = $this->makeAbcWithSupplier2Winning();

        $bac = BacResolution::create([
            'abc_id' => $abc->id,
            'title' => 'AUTHORIZING THE PROCUREMENT',
            'date' => now(),
            'place' => 'Koronadal City, Philippines',
        ]);

        $this->actingAs($admin)
            ->get(route('bac.print', $bac))
            ->assertOk()
            // Winning supplier and items stay in the form/view only.
            ->assertDontSee('Other Supplier Inc.')
            ->assertDontSee('80.00')
            ->assertDontSee('40.00')
            ->assertDontSee('1,000.00')
            // The rest of the printed resolution is preserved. The title is
            // rendered in sentence case at regular weight.
            ->assertSee('A resolution authorizing the procurement of office supplies for')
            ->assertSee('through small value procurement as provided in the Implementing Rules and')
            // The venue now comes from the closing statement rather than the
            // retired standalone dateline.
            ->assertSee('City of Koronadal, Philippines.')
            ->assertSee('Approved by:');
    }
}