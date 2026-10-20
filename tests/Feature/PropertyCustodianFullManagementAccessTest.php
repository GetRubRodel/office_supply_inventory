<?php

namespace Tests\Feature;

use App\Filament\Resources\AbstractOfCanvassResource;
use App\Filament\Resources\BacResolutionResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\InspectionAcceptanceResource;
use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Resources\PurchaseRequestResource;
use App\Filament\Resources\RequestForQuotationResource;
use App\Filament\Resources\RpciResource;
use App\Filament\Resources\SupplyResource;
use App\Filament\Resources\SupplierResource;
use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\Category;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\Requisition;
use App\Models\Rpci;
use App\Models\Supply;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Property Custodian holds full Create/View/Edit/Delete management over every
 * Purchase Management and Inventory Management module (PR, RFQ, ABC, PO, IAR,
 * BAC Resolution, Categories, Supplies, Suppliers, Inventory Report, RSMI,
 * RPCI) — enforced through the Spatie permission layer, the policies, and the
 * Filament resource gates. Approval powers and the RIS (Requisition) workflow
 * stay with their existing roles.
 */
class PropertyCustodianFullManagementAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The User model does not implement FilamentUser, so Filament's
        // panel-auth middleware permits access only when APP_ENV=local.
        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mirror RolePermissionSeeder's full permission set so sidebar and
        // resource checks never hit Spatie's PermissionDoesNotExist.
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

        // Mirror the RolePermissionSeeder role -> permission assignments.
        $rolePermissions = [
            'admin' => Permission::all()->pluck('name')->all(),
            'supply_officer' => [
                'dashboard.view',
                'supplies.view', 'supplies.create', 'supplies.update', 'supplies.delete',
                'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',
                'categories.view', 'categories.create', 'categories.update', 'categories.delete',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.update', 'purchase_requests.print',
                'request_for_quotations.view', 'request_for_quotations.create', 'request_for_quotations.update', 'request_for_quotations.delete', 'request_for_quotations.print',
                'abstracts_of_canvass.view', 'abstracts_of_canvass.create', 'abstracts_of_canvass.update', 'abstracts_of_canvass.delete', 'abstracts_of_canvass.print',
                'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.update', 'purchase_orders.delete', 'purchase_orders.print',
                'inspection_acceptance_reports.view', 'inspection_acceptance_reports.create', 'inspection_acceptance_reports.stock_in', 'inspection_acceptance_reports.print',
                'bac_resolutions.view', 'bac_resolutions.create', 'bac_resolutions.update', 'bac_resolutions.delete', 'bac_resolutions.print',
                'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.issue', 'requisitions.print',
                'rpcis.view', 'rpcis.create', 'rpcis.update', 'rpcis.delete', 'rpcis.print', 'rpcis.preview',
                'rsmi_reports.view', 'rsmi_reports.create', 'rsmi_reports.update', 'rsmi_reports.delete', 'rsmi_reports.print',
                'inventory_reports.view', 'inventory_reports.create', 'inventory_reports.update', 'inventory_reports.delete', 'inventory_reports.print',
                'stock_in.view', 'stock_in.create', 'stock_in.update', 'stock_in.delete',
            ],
            // Property Custodian — full CRUD on every Purchase Management and
            // Inventory Management module. No approve powers; RIS scope is
            // view/create/print only.
            'property_custodian' => [
                'dashboard.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.update', 'purchase_requests.delete', 'purchase_requests.print',
                'request_for_quotations.view', 'request_for_quotations.create', 'request_for_quotations.update', 'request_for_quotations.delete', 'request_for_quotations.print',
                'abstracts_of_canvass.view', 'abstracts_of_canvass.create', 'abstracts_of_canvass.update', 'abstracts_of_canvass.delete', 'abstracts_of_canvass.print',
                'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.update', 'purchase_orders.delete', 'purchase_orders.print',
                'inspection_acceptance_reports.view', 'inspection_acceptance_reports.create', 'inspection_acceptance_reports.update', 'inspection_acceptance_reports.delete', 'inspection_acceptance_reports.stock_in', 'inspection_acceptance_reports.print',
                'bac_resolutions.view', 'bac_resolutions.create', 'bac_resolutions.update', 'bac_resolutions.delete', 'bac_resolutions.print',
                'categories.view', 'categories.create', 'categories.update', 'categories.delete',
                'supplies.view', 'supplies.create', 'supplies.update', 'supplies.delete',
                'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',
                'inventory_reports.view', 'inventory_reports.create', 'inventory_reports.update', 'inventory_reports.delete', 'inventory_reports.print',
                'rsmi_reports.view', 'rsmi_reports.create', 'rsmi_reports.update', 'rsmi_reports.delete', 'rsmi_reports.print',
                'rpcis.view', 'rpcis.create', 'rpcis.update', 'rpcis.delete', 'rpcis.print', 'rpcis.preview',
                'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.print',
            ],
            'staff' => [
                'dashboard.view', 'supplies.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.print',
                'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.receive', 'requisitions.print',
            ],
            'division_chief' => [
                'dashboard.view', 'supplies.view', 'suppliers.view', 'categories.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.print',
                'requisitions.view', 'requisitions.create', 'requisitions.approve', 'requisitions.update', 'requisitions.receive', 'requisitions.print',
                'request_for_quotations.view', 'request_for_quotations.print',
                'abstracts_of_canvass.view', 'abstracts_of_canvass.print',
                'purchase_orders.view', 'purchase_orders.print',
                'inspection_acceptance_reports.view', 'inspection_acceptance_reports.print',
                'bac_resolutions.view', 'bac_resolutions.print',
                'inventory_reports.view',
                'rpcis.view',
                'rsmi_reports.view',
            ],
            'regional_director' => [
                'dashboard.view', 'supplies.view', 'suppliers.view', 'categories.view', 'stock_in.view',
                'inventory_reports.view',
                'rsmi_reports.view',
                'rpcis.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.approve', 'purchase_requests.print',
                'request_for_quotations.view', 'request_for_quotations.print',
                'abstracts_of_canvass.view', 'abstracts_of_canvass.print',
                'purchase_orders.view', 'purchase_orders.print',
                'inspection_acceptance_reports.view', 'inspection_acceptance_reports.print',
                'bac_resolutions.view', 'bac_resolutions.print',
                'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.print',
            ],
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

    private function makeRecord(string $class, array $overrides = []): object
    {
        $defaults = match ($class) {
            PurchaseRequest::class => [
                'date' => now(),
                'office_division' => 'Administrative Division',
                'name_of_project' => 'Office Supplies Procurement',
                'purpose' => 'Replenish office supplies',
                'source_of_fund' => 'GAA',
                'status' => PurchaseRequest::STATUS_FOR_REVIEW,
            ],
            RequestForQuotation::class => [
                'date' => now(),
                // RFQs must originate from an existing, approved Purchase
                // Request.
                'purchase_request_id' => PurchaseRequest::create([
                    'date' => now()->subDays(4),
                    'office_division' => 'Administrative Division',
                    'name_of_project' => 'Office Supplies Procurement',
                    'purpose' => 'Replenish office supplies',
                    'source_of_fund' => 'GAA',
                    'status' => PurchaseRequest::STATUS_APPROVED,
                ])->id,
            ],
            AbstractOfCanvass::class => [
                'rfq_id' => $this->makeRfq()->id,
                'date_of_advertisement' => now(),
                'date_of_opening' => now(),
            ],
            PurchaseOrder::class => [
                'date' => now(),
                // Purchase Orders must originate from an existing,
                // unconverted BAC Resolution.
                'bac_resolution_id' => BacResolution::create([
                    'abc_id' => $this->makeAbc()->id,
                    'date' => now(),
                ])->id,
            ],
            InspectionAcceptanceReport::class => [
                'date' => now(),
                'status' => InspectionAcceptanceReport::STATUS_DRAFT,
                // IARs must originate from an existing Purchase Order.
                'po_id' => $this->makePo()->id,
            ],
            BacResolution::class => [
                'date' => now(),
                // BAC Resolutions must originate from an existing,
                // unconverted Abstract of Bids and Canvass (ABC).
                'abc_id' => $this->makeAbc()->id,
            ],
            Rpci::class => ['report_no' => 'RPCI-2026-'.uniqid(), 'report_date' => now()],
            Category::class => ['name' => 'Test Category', 'description' => 'Test category'],
            Supply::class => [
                'name' => 'Test Supply',
                'stock_no' => 'STK-TEST-'.uniqid(),
                'unit' => 'pcs',
                'category_id' => Category::create(['name' => 'Test Category', 'description' => 'Test category'])->id,
                'current_stock' => 0,
                'status' => 'active',
            ],
            Supplier::class => ['name' => 'Test Supplier'],
            default => [],
        };

        return $class::create(array_merge($defaults, $overrides));
    }

    /**
     * Build a valid ABC by first creating the required PR -> RFQ chain.
     */
    private function makeAbc(): AbstractOfCanvass
    {
        return AbstractOfCanvass::create([
            'rfq_id' => $this->makeRfq()->id,
            'date_of_advertisement' => now(),
            'date_of_opening' => now(),
        ]);
    }

    /**
     * Build a valid RFQ by first creating the required approved Purchase
     * Request.
     */
    private function makeRfq(): RequestForQuotation
    {
        $pr = PurchaseRequest::create([
            'date' => now()->subDays(4),
            'office_division' => 'Administrative Division',
            'name_of_project' => 'Office Supplies Procurement',
            'purpose' => 'Replenish office supplies',
            'source_of_fund' => 'GAA',
            'status' => PurchaseRequest::STATUS_APPROVED,
        ]);

        return RequestForQuotation::create([
            'purchase_request_id' => $pr->id,
            'date' => now()->subDays(3),
        ]);
    }

    /**
     * Build a valid Purchase Order by first creating the required
     * PR -> RFQ -> ABC -> BAC Resolution chain.
     */
    private function makePo(): PurchaseOrder
    {
        $bac = BacResolution::create([
            'abc_id' => $this->makeAbc()->id,
            'date' => now(),
        ]);

        return PurchaseOrder::create([
            'bac_resolution_id' => $bac->id,
            'date' => now(),
        ]);
    }

    // ─── Permission layer ─────────────────────────────────────────────

    public function test_property_custodian_holds_full_crud_permissions_on_all_modules(): void
    {
        $pc = $this->makeUser('property_custodian');

        $modules = [
            'purchase_requests',
            'request_for_quotations',
            'abstracts_of_canvass',
            'purchase_orders',
            'inspection_acceptance_reports',
            'bac_resolutions',
            'categories',
            'supplies',
            'suppliers',
            'inventory_reports',
            'rsmi_reports',
            'rpcis',
        ];

        foreach ($modules as $module) {
            $this->assertTrue($pc->hasPermissionTo("{$module}.view"), "{$module}.view");
            $this->assertTrue($pc->hasPermissionTo("{$module}.create"), "{$module}.create");
            $this->assertTrue($pc->hasPermissionTo("{$module}.update"), "{$module}.update");
            $this->assertTrue($pc->hasPermissionTo("{$module}.delete"), "{$module}.delete");
        }
    }

    public function test_property_custodian_holds_module_management_actions(): void
    {
        $pc = $this->makeUser('property_custodian');

        $this->assertTrue($pc->hasPermissionTo('inspection_acceptance_reports.stock_in'));
        foreach (['purchase_requests', 'request_for_quotations', 'abstracts_of_canvass', 'purchase_orders', 'inspection_acceptance_reports', 'bac_resolutions', 'inventory_reports', 'rsmi_reports', 'rpcis'] as $module) {
            $this->assertTrue($pc->hasPermissionTo("{$module}.print"), "{$module}.print");
        }
        $this->assertTrue($pc->hasPermissionTo('rpcis.preview'));
    }

    public function test_property_custodian_does_not_hold_approval_permissions(): void
    {
        $pc = $this->makeUser('property_custodian');

        // Approval powers remain with their existing roles (workflow preserved).
        // Note: requisitions.update IS granted to the Property Custodian so
        // they can edit their OWN submitted RIS requests — but that update is
        // scoped to their own records and is not an approval power.
        $this->assertFalse($pc->hasPermissionTo('purchase_requests.approve'));
        $this->assertFalse($pc->hasPermissionTo('requisitions.approve'));
        $this->assertFalse($pc->hasPermissionTo('requisitions.issue'));
        $this->assertFalse($pc->hasPermissionTo('requisitions.receive'));
        $this->assertFalse($pc->hasPermissionTo('requisitions.delete'));
    }

    // ─── Policy (backend authorization) layer ─────────────────────────

    public function test_policies_grant_full_crud_to_property_custodian(): void
    {
        $pc = $this->makeUser('property_custodian');

        $cases = [
            [PurchaseRequest::class, 'create', []],
            [PurchaseRequest::class, 'update', ['user_id' => $pc->id]],
            [PurchaseRequest::class, 'delete', ['user_id' => $pc->id]],
            [RequestForQuotation::class, 'create', []],
            [RequestForQuotation::class, 'update', []],
            [RequestForQuotation::class, 'delete', []],
            [AbstractOfCanvass::class, 'create', []],
            [AbstractOfCanvass::class, 'update', []],
            [AbstractOfCanvass::class, 'delete', []],
            [PurchaseOrder::class, 'create', []],
            [PurchaseOrder::class, 'update', []],
            [PurchaseOrder::class, 'delete', []],
            [InspectionAcceptanceReport::class, 'create', []],
            [InspectionAcceptanceReport::class, 'update', []],
            [InspectionAcceptanceReport::class, 'delete', []],
            [BacResolution::class, 'create', []],
            [BacResolution::class, 'update', []],
            [BacResolution::class, 'delete', []],
            [Category::class, 'create', []],
            [Category::class, 'update', []],
            [Category::class, 'delete', []],
            [Supply::class, 'create', []],
            [Supply::class, 'update', []],
            [Supply::class, 'delete', []],
            [Supplier::class, 'create', []],
            [Supplier::class, 'update', []],
            [Supplier::class, 'delete', []],
            [Rpci::class, 'create', []],
            [Rpci::class, 'update', []],
            [Rpci::class, 'delete', []],
        ];

        foreach ($cases as [$class, $action, $attrs]) {
            // Create abilities are checked against the class; update/delete
            // are bindable policies that need a record instance.
            $can = $action === 'create'
                ? $pc->can($action, $class)
                : $pc->can($action, $this->makeRecord($class, $attrs));

            $this->assertTrue($can, "PC should be allowed to {$action} {$class}");
        }
    }

    public function test_pr_approval_stays_with_existing_approvers(): void
    {
        $pc = $this->makeUser('property_custodian');
        $pr = $this->makeRecord(PurchaseRequest::class, [
            'user_id' => $pc->id,
            'date' => now(),
            'office_division' => 'Administrative Division',
            'name_of_project' => 'Office Supplies Procurement',
            'purpose' => 'Replenish office supplies',
            'source_of_fund' => 'GAA',
            'status' => PurchaseRequest::STATUS_FOR_REVIEW,
        ]);

        $this->assertFalse($pc->can('approve', $pr));
        $this->assertFalse($pc->canApprovePurchaseRequest($pr));

        // Existing approvers are untouched.
        $rd = $this->makeUser('regional_director');
        $this->assertTrue($rd->can('approve', $pr));
    }

    public function test_other_roles_are_unchanged(): void
    {
        $staff = $this->makeUser('staff');
        $rd = $this->makeUser('regional_director');
        $chief = $this->makeUser('division_chief');

        // Staff: can create PR drafts; no supply management.
        $this->assertTrue($staff->can('create', PurchaseRequest::class));
        $this->assertFalse($staff->can('create', Supply::class));
        $this->assertFalse($staff->hasPermissionTo('supplies.update'));

        // Regional Director: still read-only on procurement/inventory.
        $pr = $this->makeRecord(PurchaseRequest::class, ['user_id' => $rd->id]);
        $this->assertFalse($rd->can('update', $pr));
        $this->assertFalse($rd->can('delete', $pr));
        $this->assertFalse($rd->can('create', Supply::class));
        $this->assertFalse($rd->can('create', Supplier::class));
        $this->assertFalse($rd->canManageProcurement());
        $this->assertFalse($rd->canManageInventory());

        // Division Chief: may create PR drafts but cannot submit/approve/edit
        // them (submission stays with the original requester or Admin;
        // approval with Admin/RD).
        $this->assertTrue($chief->can('create', PurchaseRequest::class));
        $this->assertFalse($chief->can('update', $pr));
        $this->assertFalse($chief->can('approve', $pr));
        $this->assertFalse($chief->hasPermissionTo('suppliers.update'));
    }

    // ─── Filament resource (navigation/gate) layer ────────────────────

    public function test_property_custodian_resource_gates_allow_management(): void
    {
        $pc = $this->makeUser('property_custodian');
        $this->actingAs($pc);

        // Pure permission gates: access, edit and delete are honored.
        foreach ([
            PurchaseRequestResource::class,
            RequestForQuotationResource::class,
            AbstractOfCanvassResource::class,
            PurchaseOrderResource::class,
            InspectionAcceptanceResource::class,
            BacResolutionResource::class,
            CategoryResource::class,
            SupplyResource::class,
            SupplierResource::class,
            RpciResource::class,
        ] as $resource) {
            $this->assertTrue($resource::canAccess(), "{$resource}::canAccess");
        }

        $this->assertTrue(PurchaseRequestResource::canCreate());
        $this->assertTrue(PurchaseRequestResource::canEdit());
        $this->assertTrue(PurchaseRequestResource::canDelete());
        $this->assertTrue(CategoryResource::canCreate());
        $this->assertTrue(CategoryResource::canEdit());
        $this->assertTrue(CategoryResource::canDelete());
        $this->assertTrue(SupplyResource::canCreate());
        $this->assertTrue(SupplyResource::canEdit());
        $this->assertTrue(SupplyResource::canDelete());
        $this->assertTrue(SupplierResource::canCreate());
        $this->assertTrue(SupplierResource::canEdit());
        $this->assertTrue(SupplierResource::canDelete());
        $this->assertTrue(RequestForQuotationResource::canEdit());
        $this->assertTrue(RequestForQuotationResource::canDelete());
        $this->assertTrue(AbstractOfCanvassResource::canEdit());
        $this->assertTrue(AbstractOfCanvassResource::canDelete());
        $this->assertTrue(PurchaseOrderResource::canEdit());
        $this->assertTrue(PurchaseOrderResource::canDelete());
        $this->assertTrue(BacResolutionResource::canEdit());
        $this->assertTrue(BacResolutionResource::canDelete());
        $this->assertTrue(RpciResource::canEdit());
        $this->assertTrue(RpciResource::canDelete());

        // Role helpers used by the page action gates.
        $this->assertTrue($pc->canManageProcurement());
        $this->assertTrue($pc->canManageInventory());
        $this->assertTrue($pc->canManageLegal());
        $this->assertTrue($pc->canAccessMasterData());

        // The Property Custodian can open the create pages that are pure
        // permission gates (no eligible-source requirement).
        $this->get('/admin/purchase-requests/create')->assertOk();
        $this->get('/admin/supplies/create')->assertOk();
        $this->get('/admin/suppliers/create')->assertOk();
    }

    public function test_property_custodian_requisition_workflow_permissions(): void
    {
        $pc = $this->makeUser('property_custodian');
        $other = $this->makeUser('staff');
        $ris = $this->makeRecord(Requisition::class, [
            'user_id' => $pc->id,
            'division' => 'Administrative',
            'office' => 'Administrative Division',
            'entity_name' => 'CHR Region XII',
            'purpose' => 'Office supplies request',
            'status' => Requisition::STATUS_REQUESTED,
        ]);
        $others = $this->makeRecord(Requisition::class, [
            'user_id' => $other->id,
            'division' => 'Administrative',
            'office' => 'Administrative Division',
            'entity_name' => 'CHR Region XII',
            'purpose' => 'Office supplies request',
            'status' => Requisition::STATUS_REQUESTED,
        ]);

        // RIS stays view/create/update (own requests only)/print for the
        // Property Custodian. Editing another user's request and all
        // approval powers remain with the existing roles.
        $this->assertTrue($pc->can('create', Requisition::class));
        $this->assertTrue($pc->can('view', $ris));
        $this->assertTrue($pc->can('update', $ris));
        $this->assertFalse($pc->can('update', $others));
        $this->assertFalse($pc->can('delete', $ris));
        $this->assertFalse($pc->can('approve', $ris));
        $this->assertFalse($pc->can('issue', $ris));
    }
}
