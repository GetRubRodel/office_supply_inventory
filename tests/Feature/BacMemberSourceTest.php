<?php

namespace Tests\Feature;

use App\Filament\Resources\AbstractOfCanvassResource\Pages\CreateAbstractOfCanvass;
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
 * BAC Resolution member details are sourced live from the originating ABC's
 * "Committee on Awards" section via the abc() relationship. The ABC record is
 * the source of truth — the BAC never stores its own member data.
 */
class BacMemberSourceTest extends TestCase
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

    /**
     * Build the PR -> RFQ -> ABC chain with a full Committee on Awards.
     */
    private function makeAbc(array $overrides = []): AbstractOfCanvass
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

        return AbstractOfCanvass::create(array_merge([
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
        ], $overrides));
    }

    public function test_bac_member_fields_are_sourced_from_abc(): void
    {
        $abc = $this->makeAbc();

        $bac = BacResolution::create(['abc_id' => $abc->id, 'date' => now()]);

        $this->assertSame('Miguel A. Peñaloza', $bac->chairperson_name);
        $this->assertSame('Chairman', $bac->chairperson_designation);
        $this->assertSame('Atty. Mae P. Galong', $bac->vice_chairperson_name);
        $this->assertSame('Vice Chairman', $bac->vice_chairperson_designation);
        $this->assertSame('Atty. Reuben P. Escarlan', $bac->member1_name);
        $this->assertSame('Member', $bac->member1_designation);
        $this->assertSame('Arnold B. Aumento', $bac->member2_name);
        $this->assertSame('Member', $bac->member2_designation);
        $this->assertSame('Rizalyn C. Isnani-Concha', $bac->member3_name);
        $this->assertSame('Member', $bac->member3_designation);
    }

    public function test_bac_member_fields_reflect_abc_changes(): void
    {
        $abc = $this->makeAbc();
        $bac = BacResolution::create(['abc_id' => $abc->id, 'date' => now()]);

        $abc->update(['chairman_name' => 'New Chairperson']);

        $this->assertSame('New Chairperson', $bac->fresh()->chairperson_name);
    }

    public function test_bac_member_data_submitted_directly_is_ignored(): void
    {
        $abc = $this->makeAbc();

        $bac = BacResolution::create([
            'abc_id' => $abc->id,
            'date' => now(),
            'chairperson_name' => 'HACKED NAME',
            'member3_designation' => 'Hacked Designation',
        ]);

        // The ABC record remains the source of truth.
        $this->assertSame('Miguel A. Peñaloza', $bac->chairperson_name);
        $this->assertSame('Member', $bac->member3_designation);
    }

    public function test_bac_member_columns_are_shadowed_by_abc_data(): void
    {
        $abc = $this->makeAbc();
        $bac = BacResolution::create(['abc_id' => $abc->id, 'date' => now()]);
        $fresh = $bac->fresh();

        // The accessor returns ABC data while the underlying column holds the
        // legacy DB default — proving the ABC is the source of truth.
        $this->assertSame('Miguel A. Peñaloza', $fresh->chairperson_name);
        $this->assertNotSame(
            $fresh->chairperson_name,
            $fresh->getRawOriginal('chairperson_name')
        );
    }

    public function test_abc_form_persists_committee_designations(): void
    {
        $admin = $this->makeAdmin();

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

        // The RFQ must carry items — selecting it as the ABC source loads
        // those items into the ABC repeater.
        $rfq->items()->create([
            'unit' => 'Ream',
            'item_description' => 'Bond Paper',
            'quantity' => 10,
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(CreateAbstractOfCanvass::class)
            ->set('data.rfq_id', $rfq->id);

        // Fill the repeater's default row with valid prices.
        $items = $component->get('data.items');
        $this->assertNotEmpty($items, 'The items repeater should have a row to fill');
        $uuid = array_key_first($items);

        $component
            ->set("data.items.{$uuid}.unit", 'Ream')
            ->set("data.items.{$uuid}.quantity", 10)
            ->set("data.items.{$uuid}.description", 'Bond Paper')
            ->set("data.items.{$uuid}.supplier1_price", 220)
            ->set("data.items.{$uuid}.supplier2_price", 230)
            ->set("data.items.{$uuid}.supplier3_price", 240)
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
        $this->assertSame('Chairman', $abc->chairman_designation);
        $this->assertSame('Vice Chairman', $abc->vice_chairman_designation);
        $this->assertSame('Member', $abc->member1_designation);
        $this->assertSame('Member', $abc->member2_designation);
        $this->assertSame('Member', $abc->member3_designation);
    }

    public function test_abc_model_fills_missing_designations_on_create(): void
    {
        $abc = $this->makeAbc([
            'chairman_designation' => null,
            'vice_chairman_designation' => null,
            'member1_designation' => null,
            'member2_designation' => null,
            'member3_designation' => null,
        ]);

        $this->assertSame('Chairman', $abc->chairman_designation);
        $this->assertSame('Vice Chairman', $abc->vice_chairman_designation);
        $this->assertSame('Member', $abc->member1_designation);
        $this->assertSame('Member', $abc->member2_designation);
        $this->assertSame('Member', $abc->member3_designation);
    }

    public function test_abc_model_never_overwrites_existing_designations(): void
    {
        $abc = $this->makeAbc(['chairman_designation' => 'Custom Chairman']);

        $abc->update(['member1_name' => 'Someone Else']);

        $this->assertSame('Custom Chairman', $abc->fresh()->chairman_designation);
    }
}