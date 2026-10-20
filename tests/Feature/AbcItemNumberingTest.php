<?php

namespace Tests\Feature;

use App\Filament\Resources\AbstractOfCanvassResource\Pages\CreateAbstractOfCanvass;
use App\Filament\Resources\AbstractOfCanvassResource\Pages\EditAbstractOfCanvass;
use App\Models\AbstractOfCanvass;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\User;
use App\Services\AbstractOfCanvassService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * ABC item rows are numbered sequentially (1, 2, 3, …) per record — never the
 * DB primary key. Numbers are assigned automatically when rows are added and
 * re-calculated when rows are removed, both live in the form and as a safety
 * net on save.
 */
class AbcItemNumberingTest extends TestCase
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

        Role::findOrCreate('admin', 'web')->syncPermissions(Permission::all()->pluck('name')->all());
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

    private function makeRfq(int $itemCount = 2): RequestForQuotation
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

        for ($i = 1; $i <= $itemCount; $i++) {
            $rfq->items()->create([
                'unit' => 'Ream',
                'item_description' => "Item {$i}",
                'quantity' => 10 * $i,
            ]);
        }

        return $rfq;
    }

    private function makeAbc(): AbstractOfCanvass
    {
        $rfq = $this->makeRfq();

        return AbstractOfCanvass::create([
            'rfq_id' => $rfq->id,
            'supplier1_name' => 'Test Supplier Co.',
            'supplier2_name' => 'Other Supplier Inc.',
            'supplier3_name' => 'Third Supplier LLC',
            'recommendation' => 'Test Supplier Co.',
            'date_of_advertisement' => now(),
            'date_of_opening' => now(),
            'chairman_name' => 'Miguel A. Peñaloza',
            'chairman_designation' => 'Chairman',
            'vice_chairman_name' => 'Atty. Mae P. Galong',
            'vice_chairman_designation' => 'Vice Chairman',
            'member1_name' => 'Atty. Reuben P. Escarlan',
            'member1_designation' => 'Member',
            'member2_name' => 'Arnold B. Aumento',
            'member2_designation' => 'Member',
            'member3_name' => 'Rizalyn C. Isnani-Concha',
            'member3_designation' => 'Member',
            'status' => 'draft',
        ]);
    }

    public function test_renumber_items_assigns_sequential_numbers(): void
    {
        $items = [
            ['item_number' => 99, 'description' => 'A'],
            ['item_number' => 3, 'description' => 'B'],
            ['item_number' => 7, 'description' => 'C'],
        ];

        $renumbered = AbstractOfCanvassService::renumberItems($items);

        $this->assertSame([1, 2, 3], array_column($renumbered, 'item_number'));
        $this->assertSame(['A', 'B', 'C'], array_column($renumbered, 'description'));
    }

    public function test_renumber_items_preserves_row_keys(): void
    {
        $items = [
            'row-a' => ['item_number' => 5, 'description' => 'A'],
            'row-b' => ['item_number' => 2, 'description' => 'B'],
        ];

        $renumbered = AbstractOfCanvassService::renumberItems($items);

        $this->assertSame(['row-a', 'row-b'], array_keys($renumbered));
        $this->assertSame([1, 2], array_column($renumbered, 'item_number'));
    }

    public function test_renumber_items_handles_empty_input(): void
    {
        $this->assertSame([], AbstractOfCanvassService::renumberItems([]));
    }

    public function test_abc_items_are_numbered_independently_per_record(): void
    {
        $abc1 = $this->makeAbc();
        $abc1->items()->create(['item_number' => 1, 'unit' => 'Ream', 'quantity' => 10, 'description' => 'A', 'supplier1_price' => 100, 'supplier2_price' => 110, 'supplier3_price' => 120]);
        $abc1->items()->create(['item_number' => 2, 'unit' => 'Ream', 'quantity' => 20, 'description' => 'B', 'supplier1_price' => 200, 'supplier2_price' => 210, 'supplier3_price' => 220]);

        $abc2 = $this->makeAbc();
        $abc2->items()->create(['item_number' => 1, 'unit' => 'Ream', 'quantity' => 5, 'description' => 'C', 'supplier1_price' => 50, 'supplier2_price' => 55, 'supplier3_price' => 60]);

        // Each ABC restarts its own sequence — numbers are per-record, not a
        // global counter and never the DB primary key.
        $this->assertSame([1, 2], $abc1->items()->orderBy('item_number')->pluck('item_number')->all());
        $this->assertSame([1], $abc2->items()->orderBy('item_number')->pluck('item_number')->all());
    }

    public function test_abc_form_save_renumbers_items_sequentially(): void
    {
        $admin = $this->makeAdmin();
        $rfq = $this->makeRfq(2);

        $this->actingAs($admin);

        $component = Livewire::test(CreateAbstractOfCanvass::class)
            ->set('data.rfq_id', $rfq->id);

        // Selecting the RFQ loads its items with sequential numbers.
        $items = $component->get('data.items');
        $this->assertCount(2, $items);
        $this->assertSame([1, 2], array_values(array_column($items, 'item_number')));

        // Supplier prices are required per item — fill them for every row.
        foreach ($items as $uuid => $item) {
            $component
                ->set("data.items.{$uuid}.supplier1_price", 100)
                ->set("data.items.{$uuid}.supplier2_price", 110)
                ->set("data.items.{$uuid}.supplier3_price", 120);
        }

        $component
            ->fillForm([
                'date_of_advertisement' => now()->toDateString(),
                'date_of_opening' => now()->toDateString(),
                'supplier1_name' => 'Test Supplier Co.',
                'supplier2_name' => 'Other Supplier Inc.',
                'supplier3_name' => 'Third Supplier LLC',
                'chairman_name' => 'Miguel A. Peñaloza',
                'vice_chairman_name' => 'Atty. Mae P. Galong',
                'member1_name' => 'Atty. Reuben P. Escarlan',
                'member2_name' => 'Arnold B. Aumento',
                'member3_name' => 'Rizalyn C. Isnani-Concha',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $abc = AbstractOfCanvass::first();
        $this->assertNotNull($abc, 'ABC should be created');
        $this->assertSame([1, 2], $abc->items()->orderBy('item_number')->pluck('item_number')->all());
    }

    public function test_abc_form_renumbers_remaining_rows_after_removal(): void
    {
        $admin = $this->makeAdmin();
        $rfq = $this->makeRfq(2);

        $this->actingAs($admin);

        $component = Livewire::test(CreateAbstractOfCanvass::class)
            ->set('data.rfq_id', $rfq->id);

        $items = $component->get('data.items');
        $this->assertCount(2, $items);

        // Remove the first row — the remaining row must become item 1.
        $removedKey = array_key_first($items);
        unset($items[$removedKey]);

        $component->set('data.items', $items);

        $remaining = $component->get('data.items');
        $this->assertCount(1, $remaining);
        $this->assertSame([1], array_values(array_column($remaining, 'item_number')));
    }

    public function test_edit_page_renumbers_legacy_items_on_load_and_save(): void
    {
        $admin = $this->makeAdmin();
        $abc = $this->makeAbc();

        // Legacy data with non-sequential numbers (gaps).
        $abc->items()->create(['item_number' => 5, 'unit' => 'Ream', 'quantity' => 10, 'description' => 'A', 'supplier1_price' => 100, 'supplier2_price' => 110, 'supplier3_price' => 120]);
        $abc->items()->create(['item_number' => 9, 'unit' => 'Ream', 'quantity' => 20, 'description' => 'B', 'supplier1_price' => 200, 'supplier2_price' => 210, 'supplier3_price' => 220]);

        $this->actingAs($admin);

        $component = Livewire::test(EditAbstractOfCanvass::class, ['record' => $abc->getRouteKey()]);

        // afterStateHydrated renumbers the displayed rows immediately.
        $items = $component->get('data.items');
        $this->assertSame([1, 2], array_values(array_column($items, 'item_number')));

        $component
            ->call('save')
            ->assertHasNoFormErrors();

        $abc->refresh();
        $this->assertSame([1, 2], $abc->items()->orderBy('item_number')->pluck('item_number')->all());
    }
}