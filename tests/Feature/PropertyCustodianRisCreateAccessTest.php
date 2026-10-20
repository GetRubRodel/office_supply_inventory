<?php

namespace Tests\Feature;

use App\Filament\Resources\RequisitionResource;
use App\Models\PurchaseRequest;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PropertyCustodianRisCreateAccessTest extends TestCase
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

        // Mirror the production role -> permission assignments (RIS related).
        $rolePermissions = [
            'admin' => Permission::all()->pluck('name')->all(),
            'supply_officer' => [
                'dashboard.view', 'supplies.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.update', 'purchase_requests.print',
                'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.issue', 'requisitions.print',
            ],
            // Property Custodian: RIS view + create + update (own requests
            // only, enforced by RequisitionPolicy) + print. No approve/
            // issue/receive/delete.
            'property_custodian' => [
                'dashboard.view', 'supplies.view',
                'purchase_requests.view', 'purchase_requests.print',
                'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.print',
            ],
            'division_chief' => [
                'dashboard.view', 'supplies.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.print',
                'requisitions.view', 'requisitions.create', 'requisitions.approve', 'requisitions.update', 'requisitions.receive', 'requisitions.print',
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

    private function makeRequisition(int $userId, string $status = Requisition::STATUS_REQUESTED): Requisition
    {
        return Requisition::create([
            'user_id' => $userId,
            'division' => 'Administrative',
            'office' => 'Administrative Division',
            'entity_name' => 'CHR Region XII',
            'purpose' => 'Office supplies request',
            'status' => $status,
        ]);
    }

    // ─── Create authorization ────────────────────────────────────────

    public function test_property_custodian_holds_requisitions_create_permission(): void
    {
        $pc = $this->makeUser('property_custodian');

        $this->assertTrue($pc->hasPermissionTo('requisitions.create'));
    }

    public function test_property_custodian_resource_can_create(): void
    {
        $pc = $this->makeUser('property_custodian');

        $this->actingAs($pc);

        $this->assertTrue(RequisitionResource::canCreate());
        $this->assertTrue($pc->can('create', Requisition::class));
    }

    public function test_property_custodian_can_open_create_page_and_submit(): void
    {
        $pc = $this->makeUser('property_custodian');

        $this->actingAs($pc)
            ->get('/admin/requisitions/create')
            ->assertOk();

        // A submitted RIS is owned by the Property Custodian and enters the
        // existing workflow as a "requested" requisition.
        $ris = $this->makeRequisition($pc->id);
        $this->assertSame(Requisition::STATUS_REQUESTED, $ris->status);
        $this->assertSame($pc->id, $ris->user_id);
    }

    // ─── Unauthorized roles blocked (direct URL + policy) ────────────

    public function test_division_chief_can_create_ris(): void
    {
        $chief = $this->makeUser('division_chief');

        // The Division Chief holds requisitions.create, so the Create RIS
        // button and Create page are available.
        $this->assertTrue($chief->hasPermissionTo('requisitions.create'));

        $this->actingAs($chief);
        $this->assertTrue(RequisitionResource::canCreate());
        $this->assertTrue($chief->can('create', Requisition::class));

        $this->actingAs($chief)
            ->get('/admin/requisitions/create')
            ->assertOk();
    }

    public function test_regional_director_can_create_ris(): void
    {
        $rd = $this->makeUser('regional_director');

        // The Regional Director now holds requisitions.create, so the
        // Create RIS button and Create page are available.
        $this->assertTrue($rd->hasPermissionTo('requisitions.create'));

        $this->actingAs($rd);
        $this->assertTrue(RequisitionResource::canCreate());
        $this->assertTrue($rd->can('create', Requisition::class));

        // The list page exposes the Create RIS button.
        $this->actingAs($rd)
            ->get('/admin/requisitions')
            ->assertOk()
            ->assertSee('/admin/requisitions/create', false);

        // And the Create page itself is reachable.
        $this->actingAs($rd)
            ->get('/admin/requisitions/create')
            ->assertOk();
    }

    public function test_property_custodian_cannot_approve_issue_or_receive_ris(): void
    {
        $pc = $this->makeUser('property_custodian');
        $other = $this->makeUser('staff');
        $own = $this->makeRequisition($pc->id);
        $others = $this->makeRequisition($other->id, Requisition::STATUS_PENDING_RECEIPT);

        // Property Custodian may edit their OWN submitted RIS (update granted
        // by the seeder), but gains nothing else: approval, issuance,
        // receipt, deletion and editing other users' requests stay blocked.
        $this->assertTrue($pc->hasPermissionTo('requisitions.update'));
        $this->assertTrue($pc->can('update', $own));
        $this->assertFalse($pc->can('update', $others));

        $this->assertFalse($pc->can('approve', $own));
        $this->assertFalse($pc->can('issue', $own));
        $this->assertFalse($pc->can('receive', $own));
        $this->assertFalse($pc->can('delete', $own));

        $this->assertFalse($pc->canApproveRequisition($own));
        $this->assertFalse($pc->canIssueItems());
        $this->assertFalse($pc->canReceiveRequisition($own));
    }

    // ─── Approval: Administrator + Division Chief only ────────────────

    public function test_only_admin_and_division_chief_can_approve_ris(): void
    {
        $admin = $this->makeUser('admin');
        $chief = $this->makeUser('division_chief');
        $ris = $this->makeRequisition($admin->id, Requisition::STATUS_REQUESTED);

        // Both approvers use the same approval workflow: permission layer,
        // policy and the backend transition guard (canApproveRequisition).
        $this->assertTrue($admin->hasPermissionTo('requisitions.approve'));
        $this->assertTrue($chief->hasPermissionTo('requisitions.approve'));
        $this->assertTrue($admin->can('approve', $ris));
        $this->assertTrue($chief->can('approve', $ris));
        $this->assertTrue($admin->canApproveRequisition($ris));
        $this->assertTrue($chief->canApproveRequisition($ris));

        // No other role may approve — the policy denies and the backend
        // transition guard aborts any direct URL/request with a 403.
        foreach (['supply_officer', 'property_custodian', 'staff', 'regional_director'] as $role) {
            $user = $this->makeUser($role);
            $this->assertFalse($user->can('approve', $ris), "{$role} must not approve RIS");
            $this->assertFalse($user->canApproveRequisition($ris), "{$role} must not approve RIS");
        }

        $staff = $this->makeUser('staff');
        $this->actingAs($staff);

        $this->expectException(HttpException::class);
        $ris->transitionTo(Requisition::STATUS_APPROVED);
    }

    // ─── Approval: a Division Chief cannot approve their own RIS ──────

    public function test_division_chief_cannot_approve_own_ris_but_admin_can(): void
    {
        $admin = $this->makeUser('admin');
        $requester = $this->makeUser('division_chief');
        $otherChief = $this->makeUser('division_chief');

        $ris = $this->makeRequisition($requester->id, Requisition::STATUS_REQUESTED);

        // The requesting Division Chief cannot approve their own request.
        $this->assertFalse($requester->can('approve', $ris));
        $this->assertFalse($requester->canApproveRequisition($ris));

        // The Administrator retains approval authority over every request.
        $this->assertTrue($admin->can('approve', $ris));
        $this->assertTrue($admin->canApproveRequisition($ris));

        // A different Division Chief may still approve it.
        $this->assertTrue($otherChief->can('approve', $ris));
        $this->assertTrue($otherChief->canApproveRequisition($ris));

        // The backend transition guard aborts the requester's own attempt.
        $this->actingAs($requester);

        $this->expectException(HttpException::class);
        $ris->transitionTo(Requisition::STATUS_APPROVED);
    }

    public function test_ris_requested_by_non_chief_remains_approvable(): void
    {
        $admin = $this->makeUser('admin');
        $chief = $this->makeUser('division_chief');
        $requester = $this->makeUser('staff');

        $ris = $this->makeRequisition($requester->id, Requisition::STATUS_REQUESTED);

        $this->assertTrue($admin->can('approve', $ris));
        $this->assertTrue($chief->can('approve', $ris));
        $this->assertTrue($admin->canApproveRequisition($ris));
        $this->assertTrue($chief->canApproveRequisition($ris));
    }

    public function test_division_chief_requester_ris_can_still_be_cancelled(): void
    {
        $admin = $this->makeUser('admin');
        $chief = $this->makeUser('division_chief');
        $requester = $this->makeUser('division_chief');

        $ris = $this->makeRequisition($requester->id, Requisition::STATUS_REQUESTED);

        // The approval restriction must not strand these requests: cancel
        // remains available to Admin and Division Chief.
        $this->assertTrue($admin->canCancelRequisition($ris));
        $this->assertTrue($chief->canCancelRequisition($ris));

        $this->actingAs($admin);
        $ris->transitionTo(Requisition::STATUS_CANCELLED);

        $this->assertSame(Requisition::STATUS_CANCELLED, $ris->fresh()->status);
    }
}
