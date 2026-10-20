<?php

namespace Tests\Feature;

use App\Filament\Resources\InspectionAcceptanceResource\Pages\CreateInspectionAcceptanceReport;
use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IarRequisitioningOfficeSourceTest extends TestCase
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
     * Build the PR -> RFQ -> ABC -> BAC -> PO chain.
     */
    private function makeChain(string $office = 'Administrative Division'): PurchaseOrder
    {
        $owner = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
        ]);

        $pr = PurchaseRequest::create([
            'user_id' => $owner->id,
            'date' => '2026-08-01',
            'office_division' => $office,
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
            'supplier1_name' => 'Test Supplier Co.',
            'supplier2_name' => 'Other Supplier Inc.',
            'supplier3_name' => 'Third Supplier LLC',
            'recommendation' => 'Test Supplier Co.',
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
            'supplier_name' => 'Test Supplier Co.',
            'address' => 'Koronadal City',
            'tin' => '123456789',
            'date' => '2026-08-10',
            'mode_of_procurement' => 'Public Bidding',
        ]);
    }

    public function test_iar_form_auto_populates_office_from_source_pr(): void
    {
        $admin = $this->makeUser('admin');
        $po = $this->makeChain('Investigation Division');

        $this->actingAs($admin);

        $component = Livewire::test(CreateInspectionAcceptanceReport::class)
            ->fillForm([
                'po_id' => $po->id,
            ]);

        $this->assertSame(
            'Investigation Division',
            $component->get('data.requisitioning_office_dept'),
            'The Requisitioning Office / Department must be auto-populated from the source PR office.',
        );
    }

    public function test_iar_accessor_sources_office_live_from_source_pr(): void
    {
        $po = $this->makeChain('Legal Division');

        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
            'requisitioning_office_dept' => 'STALE VALUE',
        ]);

        $this->assertSame('Legal Division', $iar->requisitioning_office_dept);
    }

    public function test_iar_accessor_falls_back_to_legacy_po_abc_link(): void
    {
        $po = $this->makeChain('Promotion and Advocacy Division');

        // Simulate a legacy PO that predates the BAC Resolution source:
        // bac_resolution_id is null, but the direct ABC link remains.
        PurchaseOrder::query()->where('id', $po->id)->update(['bac_resolution_id' => null]);

        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
        ]);

        $this->assertSame('Promotion and Advocacy Division', $iar->requisitioning_office_dept);
    }

    public function test_iar_accessor_falls_back_to_stored_column_when_chain_is_missing(): void
    {
        // Legacy PO with no BAC Resolution and no ABC link (inserted through
        // the query builder to bypass the model-level source guard).
        PurchaseOrder::query()->insert([
            'po_no' => 'PO-2026-9999',
            'date' => '2026-08-10',
            'supplier_name' => 'Legacy Supplier',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $po = PurchaseOrder::where('po_no', 'PO-2026-9999')->firstOrFail();

        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
            'requisitioning_office_dept' => 'Administrative Division',
        ]);

        $this->assertSame('Administrative Division', $iar->requisitioning_office_dept);
    }

    public function test_iar_print_view_shows_pr_sourced_office(): void
    {
        $admin = $this->makeUser('admin');
        $po = $this->makeChain('Legal Division');

        $iar = InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'date' => '2026-08-18',
            'requisitioning_office_dept' => 'STALE VALUE',
        ]);

        $this->actingAs($admin)
            ->get(route('iar.print', $iar))
            ->assertOk()
            ->assertSee('Legal Division');
    }
}