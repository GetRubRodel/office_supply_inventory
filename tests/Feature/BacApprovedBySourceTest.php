<?php

namespace Tests\Feature;

use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * BAC Resolution approval details are sourced live from the originating
 * ABC's "Approved by" section via the abc() relationship. The ABC record is
 * the source of truth — the BAC never stores its own approval data.
 */
class BacApprovedBySourceTest extends TestCase
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
     * Build the PR -> RFQ -> ABC chain and set the approval details on the
     * originating Purchase Request (the true source of the approval data).
     */
    private function makeAbc(array $overrides = []): AbstractOfCanvass
    {
        $pr = PurchaseRequest::create(array_merge([
            'date' => now()->subDays(4),
            'office_division' => 'Administrative Division',
            'name_of_project' => 'Supply of Office Materials',
            'purpose' => 'Replenish office materials',
            'source_of_fund' => 'GAA',
            'status' => PurchaseRequest::STATUS_APPROVED,
            'approved_by_name' => 'JUAN DELA CRUZ',
            'approved_by_designation' => 'Head of the Procuring Entity',
        ], $overrides['pr'] ?? []));

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
            'status' => 'draft',
        ], $overrides['abc'] ?? []));
    }

    public function test_bac_approved_by_fields_are_sourced_from_abc(): void
    {
        $abc = $this->makeAbc();

        $bac = BacResolution::create(['abc_id' => $abc->id, 'date' => now()]);

        $this->assertSame('JUAN DELA CRUZ', $bac->approved_by_name);
        $this->assertSame('Head of the Procuring Entity', $bac->approved_by_designation);
    }

    public function test_bac_approved_by_fields_reflect_abc_changes(): void
    {
        $abc = $this->makeAbc();
        $bac = BacResolution::create(['abc_id' => $abc->id, 'date' => now()]);

        $abc->rfq->purchaseRequest->update(['approved_by_name' => 'MARIA SANTOS']);

        $this->assertSame('MARIA SANTOS', $bac->fresh()->approved_by_name);
    }

    public function test_bac_approved_by_data_submitted_directly_is_ignored(): void
    {
        $abc = $this->makeAbc();

        $bac = BacResolution::create([
            'abc_id' => $abc->id,
            'date' => now(),
            'approved_by_name' => 'HACKED NAME',
            'approved_by_designation' => 'Hacked Designation',
        ]);

        // The ABC record remains the source of truth.
        $this->assertSame('JUAN DELA CRUZ', $bac->approved_by_name);
        $this->assertSame('Head of the Procuring Entity', $bac->approved_by_designation);
    }

    public function test_bac_approved_by_columns_are_shadowed_by_abc_data(): void
    {
        $abc = $this->makeAbc();
        $bac = BacResolution::create(['abc_id' => $abc->id, 'date' => now()]);
        $fresh = $bac->fresh();

        // The accessor returns ABC data while the underlying column holds the
        // legacy DB value — proving the ABC is the source of truth.
        $this->assertSame('JUAN DELA CRUZ', $fresh->approved_by_name);
        $this->assertNotSame(
            $fresh->approved_by_name,
            $fresh->getRawOriginal('approved_by_name')
        );
    }
}