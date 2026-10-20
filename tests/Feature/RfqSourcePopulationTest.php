<?php

namespace Tests\Feature;

use App\Filament\Resources\RequestForQuotationResource\Pages\CreateRequestForQuotation;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\RequestForQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RfqSourcePopulationTest extends TestCase
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
     * Build an approved Purchase Request with two line items.
     */
    private function makeApprovedPrWithItems(): PurchaseRequest
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

        PurchaseRequestItem::create([
            'purchase_request_id' => $pr->id,
            'stock_property_no' => 'STK-001',
            'unit' => 'Box',
            'item_description' => 'Staple wire #35',
            'quantity' => 5,
            'unit_cost' => 31.00,
        ]);
        PurchaseRequestItem::create([
            'purchase_request_id' => $pr->id,
            'stock_property_no' => 'STK-002',
            'unit' => 'Ream',
            'item_description' => 'Bond paper A4',
            'quantity' => 10,
            'unit_cost' => 205.00,
        ]);

        return $pr;
    }

    public function test_selecting_pr_populates_rfq_items(): void
    {
        $admin = $this->makeUser('admin');
        $pr = $this->makeApprovedPrWithItems();

        $this->actingAs($admin);

        $component = Livewire::test(CreateRequestForQuotation::class)
            ->fillForm([
                'purchase_request_id' => $pr->id,
            ]);

        $items = $component->get('data.items');
        $this->assertNotEmpty($items, 'Items should be populated from the PR');
        $this->assertCount(2, $items);

        $first = array_values($items)[0];
        $this->assertSame('Box', $first['unit']);
        $this->assertSame('Staple wire #35', $first['item_description']);
        $this->assertSame(5, $first['quantity']);
    }

    public function test_rfq_create_saves_pr_item_details(): void
    {
        $admin = $this->makeUser('admin');
        $pr = $this->makeApprovedPrWithItems();

        $this->actingAs($admin);

        $component = Livewire::test(CreateRequestForQuotation::class)
            ->fillForm([
                'purchase_request_id' => $pr->id,
            ]);

        $items = $component->get('data.items');
        $this->assertNotEmpty($items);

        $component
            ->fillForm([
                'date' => now()->toDateString(),
                'canvassed_by_name' => 'Rodel C. Linugao',
                'canvassed_by_designation' => 'Administrative Assistant II',
                'items' => $items,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $rfq = RequestForQuotation::first();
        $this->assertNotNull($rfq, 'RFQ should be created');
        $this->assertSame($pr->id, $rfq->purchase_request_id);

        $rfqItems = $rfq->items()->orderBy('id')->get();
        $this->assertCount(2, $rfqItems);

        $this->assertSame('STK-001', $rfqItems[0]->stock_property_no);
        $this->assertSame('Box', $rfqItems[0]->unit);
        $this->assertSame('Staple wire #35', $rfqItems[0]->item_description);
        $this->assertSame(5, $rfqItems[0]->quantity);

        $this->assertSame('STK-002', $rfqItems[1]->stock_property_no);
        $this->assertSame('Ream', $rfqItems[1]->unit);
        $this->assertSame('Bond paper A4', $rfqItems[1]->item_description);
        $this->assertSame(10, $rfqItems[1]->quantity);
    }

    public function test_switching_pr_replaces_items_without_duplicates(): void
    {
        $admin = $this->makeUser('admin');
        $pr1 = $this->makeApprovedPrWithItems();
        $pr2 = $this->makeApprovedPrWithItems();

        $this->actingAs($admin);

        $component = Livewire::test(CreateRequestForQuotation::class)
            ->fillForm([
                'purchase_request_id' => $pr1->id,
            ]);

        $items1 = $component->get('data.items');
        $this->assertCount(2, $items1);

        // Switch to the second PR — items must be replaced, not duplicated.
        $component->set('data.purchase_request_id', $pr2->id);

        $items2 = $component->get('data.items');
        $this->assertCount(2, $items2, 'Switching PR must replace items, not duplicate them');
    }
}