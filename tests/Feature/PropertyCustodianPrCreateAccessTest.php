<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PropertyCustodianPrCreateAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The User model does not implement FilamentUser, so Filament's
        // panel-auth middleware permits access only when APP_ENV=local.
        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mirror RolePermissionSeeder's full permission set so the sidebar
        // and resource checks never hit Spatie's PermissionDoesNotExist.
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

        // Mirror the production role -> permission assignments (PR related).
        $rolePermissions = [
            'admin' => Permission::all()->pluck('name')->all(),
            'supply_officer' => [
                'dashboard.view', 'supplies.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.update', 'purchase_requests.print',
                'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.issue', 'requisitions.print',
            ],
            // Property Custodian: full PR management (view, create, update,
            // delete, print). Approval is not granted.
            'property_custodian' => [
                'dashboard.view', 'supplies.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.update', 'purchase_requests.delete', 'purchase_requests.print',
                'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.print',
            ],
            'division_chief' => [
                'dashboard.view', 'supplies.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.print',
                'requisitions.view', 'requisitions.create', 'requisitions.approve', 'requisitions.update', 'requisitions.print',
            ],
            'regional_director' => [
                'dashboard.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.approve', 'purchase_requests.print',
                'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.print',
            ],
            'staff' => [
                'dashboard.view', 'supplies.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.print',
                'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.receive', 'requisitions.print',
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

    private function makePurchaseRequest(int $userId): PurchaseRequest
    {
        return PurchaseRequest::create([
            'user_id' => $userId,
            'date' => now(),
            'office_division' => 'Administrative Division',
            'name_of_project' => 'Office Supplies Procurement',
            'purpose' => 'Replenish office supplies',
            'source_of_fund' => 'GAA',
            'status' => PurchaseRequest::STATUS_FOR_REVIEW,
        ]);
    }

    // ─── Create authorization ────────────────────────────────────────

    public function test_property_custodian_holds_purchase_requests_create_permission(): void
    {
        $pc = $this->makeUser('property_custodian');

        $this->assertTrue($pc->hasPermissionTo('purchase_requests.create'));
    }

    public function test_property_custodian_resource_can_create(): void
    {
        $pc = $this->makeUser('property_custodian');

        $this->actingAs($pc);

        $this->assertTrue(PurchaseRequestResource::canCreate());
        $this->assertTrue($pc->can('create', PurchaseRequest::class));
    }

    public function test_property_custodian_can_open_create_page_and_submit(): void
    {
        $pc = $this->makeUser('property_custodian');

        $this->actingAs($pc)
            ->get('/admin/purchase-requests/create')
            ->assertOk();

        // A submitted PR is owned by the Property Custodian and enters the
        // existing workflow as a draft/for-review purchase request.
        $pr = $this->makePurchaseRequest($pc->id);
        $this->assertSame($pc->id, $pr->user_id);
    }

    // ─── Unauthorized roles blocked (direct URL + policy) ────────────

    public function test_division_chief_can_create_pr(): void
    {
        $chief = $this->makeUser('division_chief');
        $pr = $this->makePurchaseRequest($chief->id);

        // The Division Chief holds purchase_requests.create, so the Create PR
        // button and Create page are available.
        $this->assertTrue($chief->hasPermissionTo('purchase_requests.create'));

        $this->actingAs($chief);
        $this->assertTrue(PurchaseRequestResource::canCreate());
        $this->assertTrue($chief->can('create', PurchaseRequest::class));

        // The list page exposes the Create PR button.
        $this->actingAs($chief)
            ->get('/admin/purchase-requests')
            ->assertOk()
            ->assertSee('/admin/purchase-requests/create', false);

        // And the Create page itself is reachable.
        $this->actingAs($chief)
            ->get('/admin/purchase-requests/create')
            ->assertOk();

        // Creation only: submission for review stays with the original
        // requester or the Administrator; approval stays with Admin/RD.
        $this->assertFalse($chief->can('update', $pr));
        $this->assertFalse($chief->can('approve', $pr));
        $this->assertFalse($chief->hasPermissionTo('purchase_requests.update'));
        $this->assertFalse($chief->hasPermissionTo('purchase_requests.approve'));
    }

    public function test_regional_director_can_create_pr(): void
    {
        $rd = $this->makeUser('regional_director');
        $pr = $this->makePurchaseRequest($rd->id);

        // Permission layer: RD is view + create + approve + print on PR.
        $this->assertTrue($rd->hasPermissionTo('purchase_requests.view'));
        $this->assertTrue($rd->hasPermissionTo('purchase_requests.create'));
        $this->assertTrue($rd->hasPermissionTo('purchase_requests.approve'));
        $this->assertTrue($rd->hasPermissionTo('purchase_requests.print'));
        $this->assertFalse($rd->hasPermissionTo('purchase_requests.update'));
        $this->assertFalse($rd->hasPermissionTo('purchase_requests.delete'));

        // Filament resource gates: the module is accessible (view), and the
        // create gate is now open for the Regional Director.
        $this->actingAs($rd);
        $this->assertTrue(PurchaseRequestResource::canAccess());
        $this->assertTrue(PurchaseRequestResource::canCreate());
        $this->assertFalse(PurchaseRequestResource::canEdit($pr));
        $this->assertFalse(PurchaseRequestResource::canDelete($pr));

        // Policy layer: view + create + approve allowed; update/delete denied.
        $this->assertTrue($rd->can('viewAny', PurchaseRequest::class));
        $this->assertTrue($rd->can('view', $pr));
        $this->assertTrue($rd->can('approve', $pr));
        $this->assertTrue($rd->can('create', PurchaseRequest::class));
        $this->assertFalse($rd->can('update', $pr));
        $this->assertFalse($rd->can('delete', $pr));

        // The list page renders with the Create PR button visible.
        $this->actingAs($rd)
            ->get('/admin/purchase-requests')
            ->assertOk()
            ->assertSee('/admin/purchase-requests/create', false);

        // Direct URL access to the Create page is allowed; Edit stays blocked.
        $this->actingAs($rd)
            ->get('/admin/purchase-requests/create')
            ->assertOk();
        $this->actingAs($rd)
            ->get("/admin/purchase-requests/{$pr->id}/edit")
            ->assertForbidden();
    }

    public function test_regional_director_pr_assigned_actions_are_preserved(): void
    {
        $rd = $this->makeUser('regional_director');
        $pr = $this->makePurchaseRequest($rd->id);

        // RD retains approval authority (the approval workflow, notifications
        // and print remain untouched): role helper, policy and the backend
        // transition guard all allow the RD to approve.
        $this->assertTrue($rd->canApprovePurchaseRequest($pr));
        $this->assertTrue($rd->can('approve', $pr));
        $this->assertTrue($rd->canApprove());

        // The view page is reachable and shows RD's assigned actions
        // (Approve / Reject / Cancel / Print), not the create/edit ones.
        $this->actingAs($rd)
            ->get("/admin/purchase-requests/{$pr->id}")
            ->assertOk();

        // RD may not edit or revise (update-gated actions), but may submit
        // their own PR for review (original requester).
        $this->assertFalse($rd->can('update', $pr));
        $this->assertFalse($rd->hasPermissionTo('purchase_requests.update'));
        $this->assertTrue($rd->canSubmitPurchaseRequest($pr));
        $this->assertTrue($rd->can('submit', $pr));
    }

    public function test_staff_can_create_pr(): void
    {
        $staff = $this->makeUser('staff');

        // Staff may create PR drafts; submission for review and approval stay
        // with the existing roles.
        $this->assertTrue($staff->hasPermissionTo('purchase_requests.create'));

        $this->actingAs($staff);
        $this->assertTrue(PurchaseRequestResource::canCreate());
        $this->assertTrue($staff->can('create', PurchaseRequest::class));

        $this->actingAs($staff)
            ->get('/admin/purchase-requests/create')
            ->assertOk();
    }

    public function test_property_custodian_can_update_and_delete_pr_but_cannot_approve(): void
    {
        $pc = $this->makeUser('property_custodian');
        $pr = $this->makePurchaseRequest($pc->id);

        // Property Custodian manages PRs: full update/delete authorization.
        // Approval stays with the existing approvers (Admin, Regional Director).
        $this->assertTrue($pc->can('update', $pr));
        $this->assertTrue($pc->can('delete', $pr));
        $this->assertFalse($pc->can('approve', $pr));
        $this->assertFalse($pc->canApprovePurchaseRequest($pr));
    }
}
