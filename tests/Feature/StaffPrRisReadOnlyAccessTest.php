<?php

namespace Tests\Feature;

use App\Filament\Resources\RequisitionResource;
use App\Filament\Widgets\RecentTransactionsWidget;
use App\Models\PurchaseRequest;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StaffPrRisReadOnlyAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The User model does not implement FilamentUser, so Filament's panel-auth
        // middleware permits access only when APP_ENV=local (as in production).
        // Mirror that here so panel access is governed by the app's own checks.
        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mirror RolePermissionSeeder's full permission set so sidebar navigation
        // (which mounts every page and checks its *.view permission) never hits
        // Spatie's PermissionDoesNotExist.
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

        // Mirror the production role -> permission assignments (PR/RIS related).
        $rolePermissions = [
            'admin' => Permission::all()->pluck('name')->all(),
            'supply_officer' => [
                'dashboard.view', 'supplies.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.update', 'purchase_requests.print',
                'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.issue', 'requisitions.receive', 'requisitions.print',
            ],
            'property_custodian' => [
                'dashboard.view', 'supplies.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.print',
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
            // Staff: view-only on PR; on RIS they may create, edit, print and
            // receive their OWN requests (never anyone else's).
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

    private function makeRequisition(int $userId): Requisition
    {
        return Requisition::create([
            'user_id' => $userId,
            'division' => 'Administrative',
            'office' => 'Administrative Division',
            'entity_name' => 'CHR Region XII',
            'purpose' => 'Office supplies request',
            'status' => Requisition::STATUS_REQUESTED,
        ]);
    }

    // ─── PR: full-list access + Create button ───────────────────────

    public function test_staff_sees_full_pr_list_including_other_users_records(): void
    {
        $other = $this->makeUser('staff');
        $staff = $this->makeUser('staff');
        $pr = $this->makePurchaseRequest($other->id);

        $this->actingAs($staff)
            ->get('/admin/purchase-requests')
            ->assertOk()
            ->assertSee($pr->pr_no, false)
            ->assertSee('/admin/purchase-requests/create', false);
    }

    // ─── RIS: own-records-only + Create button ──────────────────────

    public function test_staff_sees_only_own_ris_and_the_create_button(): void
    {
        $other = $this->makeUser('staff');
        $staff = $this->makeUser('staff');
        $ownRis = $this->makeRequisition($staff->id);
        $otherRis = $this->makeRequisition($other->id);

        $this->actingAs($staff)
            ->get('/admin/requisitions')
            ->assertOk()
            ->assertSee($ownRis->ris_no, false)
            ->assertDontSee($otherRis->ris_no, false)
            ->assertSee('/admin/requisitions/create', false);
    }

    public function test_staff_resource_can_create(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff);

        $this->assertTrue(RequisitionResource::canCreate());
        $this->assertTrue($staff->can('create', Requisition::class));
    }

    public function test_staff_policy_scopes_ris_view_to_own_records(): void
    {
        $other = $this->makeUser('staff');
        $staff = $this->makeUser('staff');
        $pr = $this->makePurchaseRequest($other->id);
        $ownRis = $this->makeRequisition($staff->id);
        $otherRis = $this->makeRequisition($other->id);

        $this->assertTrue($staff->can('view', $pr));
        $this->assertTrue($staff->can('view', $ownRis));
        $this->assertFalse($staff->can('view', $otherRis));
    }

    public function test_staff_can_open_own_ris_but_not_other_users_ris(): void
    {
        $other = $this->makeUser('staff');
        $staff = $this->makeUser('staff');
        $pr = $this->makePurchaseRequest($other->id);
        $ownRis = $this->makeRequisition($staff->id);
        $otherRis = $this->makeRequisition($other->id);

        $this->actingAs($staff)
            ->get("/admin/purchase-requests/{$pr->id}")
            ->assertOk();

        $this->actingAs($staff)
            ->get("/admin/requisitions/{$ownRis->id}")
            ->assertOk();

        // Direct URL to another user's RIS resolves through the scoped query,
        // so the record is not found for Staff.
        $this->actingAs($staff)
            ->get("/admin/requisitions/{$otherRis->id}")
            ->assertNotFound();
    }

    public function test_staff_cannot_print_prs_and_can_print_own_ris_but_not_other_users(): void
    {
        $other = $this->makeUser('staff');
        $staff = $this->makeUser('staff');
        $pr = $this->makePurchaseRequest($other->id);
        $ownRis = $this->makeRequisition($staff->id);
        $otherRis = $this->makeRequisition($other->id);

        $this->actingAs($staff)
            ->get("/pr/print/{$pr->id}")
            ->assertForbidden();

        $this->actingAs($staff)
            ->get("/ris/print/{$ownRis->id}")
            ->assertOk();

        $this->actingAs($staff)
            ->get("/ris/print/{$otherRis->id}")
            ->assertForbidden();
    }

    public function test_staff_dashboard_shows_only_own_recent_transactions(): void
    {
        $other = $this->makeUser('staff');
        $staff = $this->makeUser('staff');

        // An issued/received RIS owned by another user with issued line items.
        $otherRis = $this->makeRequisition($other->id);
        $otherRis->update(['status' => Requisition::STATUS_RECEIVED]);
        RequisitionItem::create([
            'requisition_id' => $otherRis->id,
            'quantity_requested' => 2,
            'quantity_issued' => 2,
        ]);

        // The staff member's own issued RIS.
        $ownRis = $this->makeRequisition($staff->id);
        $ownRis->update(['status' => Requisition::STATUS_RECEIVED]);
        RequisitionItem::create([
            'requisition_id' => $ownRis->id,
            'quantity_requested' => 1,
            'quantity_issued' => 1,
        ]);

        $this->actingAs($staff);

        // The dashboard renders widgets lazily, so mount the widget directly
        // and assert it surfaces only the staff member's own transactions.
        \Livewire\Livewire::test(RecentTransactionsWidget::class)
            ->assertOk()
            ->assertSee($ownRis->ris_no, false)
            ->assertDontSee($otherRis->ris_no, false);
    }

    // ─── Write operations: own records allowed, others denied ───────

    public function test_staff_can_create_pr_and_ris(): void
    {
        $staff = $this->makeUser('staff');

        // Staff can create Purchase Request drafts and RIS requests.
        $this->assertTrue($staff->can('create', PurchaseRequest::class));
        $this->assertTrue($staff->can('create', Requisition::class));

        $this->actingAs($staff)
            ->get('/admin/purchase-requests/create')
            ->assertOk();

        $this->actingAs($staff)
            ->get('/admin/requisitions/create')
            ->assertOk();
    }

    public function test_staff_can_edit_own_ris_but_not_other_users_ris(): void
    {
        $other = $this->makeUser('staff');
        $staff = $this->makeUser('staff');
        $pr = $this->makePurchaseRequest($other->id);
        $ownRis = $this->makeRequisition($staff->id);
        $otherRis = $this->makeRequisition($other->id);

        // PR: no update/delete for staff.
        $this->assertFalse($staff->can('update', $pr));
        $this->assertFalse($staff->can('delete', $pr));

        // RIS: staff may update their own, never another user's; never delete.
        $this->assertTrue($staff->can('update', $ownRis));
        $this->assertFalse($staff->can('update', $otherRis));
        $this->assertFalse($staff->can('delete', $ownRis));
        $this->assertFalse($staff->can('delete', $otherRis));

        $this->actingAs($staff)
            ->get("/admin/purchase-requests/{$pr->id}/edit")
            ->assertForbidden();

        $this->actingAs($staff)
            ->get("/admin/requisitions/{$ownRis->id}/edit")
            ->assertOk();

        // Direct URL to another user's RIS edit page resolves through the
        // scoped query, so the record is not found for Staff.
        $this->actingAs($staff)
            ->get("/admin/requisitions/{$otherRis->id}/edit")
            ->assertNotFound();
    }

    public function test_staff_cannot_approve_issue_or_delete_any_ris(): void
    {
        $other = $this->makeUser('staff');
        $staff = $this->makeUser('staff');
        $ownRis = $this->makeRequisition($staff->id);
        $otherRis = $this->makeRequisition($other->id);

        $this->assertFalse($staff->can('approve', $ownRis));
        $this->assertFalse($staff->can('approve', $otherRis));
        $this->assertFalse($staff->can('issue', $ownRis));
        $this->assertFalse($staff->can('issue', $otherRis));
        $this->assertFalse($staff->can('delete', $ownRis));
        $this->assertFalse($staff->can('delete', $otherRis));

        // Role helpers must also deny the write operations.
        $this->assertFalse($staff->canApproveRequisition($ownRis));
        $this->assertFalse($staff->canIssueItems());
    }

    public function test_staff_can_receive_only_own_ris(): void
    {
        $other = $this->makeUser('staff');
        $staff = $this->makeUser('staff');
        $ownRis = $this->makeRequisition($staff->id);
        $otherRis = $this->makeRequisition($other->id);

        // Staff may receive their own issued RIS, never another user's.
        $this->assertTrue($staff->can('receive', $ownRis));
        $this->assertFalse($staff->can('receive', $otherRis));
        $this->assertTrue($staff->canReceiveRequisition($ownRis));
        $this->assertFalse($staff->canReceiveRequisition($otherRis));
    }

    public function test_supply_officer_can_receive_only_own_ris(): void
    {
        $other = $this->makeUser('staff');
        $so = $this->makeUser('supply_officer');
        $ownRis = $this->makeRequisition($so->id);
        $otherRis = $this->makeRequisition($other->id);

        // Supply Officers may receive their own issued RIS, never another
        // user's.
        $this->assertTrue($so->can('receive', $ownRis));
        $this->assertFalse($so->can('receive', $otherRis));
        $this->assertTrue($so->canReceiveRequisition($ownRis));
        $this->assertFalse($so->canReceiveRequisition($otherRis));
    }

    public function test_supply_officer_can_receive_own_issued_ris_transition(): void
    {
        $so = $this->makeUser('supply_officer');
        $ris = $this->makeRequisition($so->id);
        // Legacy/stranded rows may sit at `issued` without the pending_receipt
        // step; the requester must still be able to acknowledge receipt.
        $ris->update(['status' => Requisition::STATUS_ISSUED]);

        $this->actingAs($so);

        $ris->transitionTo(Requisition::STATUS_RECEIVED);

        $this->assertSame(Requisition::STATUS_RECEIVED, $ris->fresh()->status);
    }

    public function test_division_chief_can_receive_only_own_ris(): void
    {
        $other = $this->makeUser('staff');
        $chief = $this->makeUser('division_chief');
        $ownRis = $this->makeRequisition($chief->id);
        $otherRis = $this->makeRequisition($other->id);

        // Division Chief may receive their own issued RIS, never another user's.
        $this->assertTrue($chief->can('receive', $ownRis));
        $this->assertFalse($chief->can('receive', $otherRis));
        $this->assertTrue($chief->canReceiveRequisition($ownRis));
        $this->assertFalse($chief->canReceiveRequisition($otherRis));
    }

    public function test_division_chief_can_receive_own_ris_transition(): void
    {
        $chief = $this->makeUser('division_chief');
        $ris = $this->makeRequisition($chief->id);
        $ris->update(['status' => Requisition::STATUS_PENDING_RECEIPT]);

        $this->actingAs($chief);

        $ris->transitionTo(Requisition::STATUS_RECEIVED);

        $this->assertSame(Requisition::STATUS_RECEIVED, $ris->fresh()->status);
    }

    public function test_division_chief_cannot_receive_other_users_ris_transition(): void
    {
        $other = $this->makeUser('staff');
        $chief = $this->makeUser('division_chief');
        $ris = $this->makeRequisition($other->id);
        $ris->update(['status' => Requisition::STATUS_PENDING_RECEIPT]);

        $this->actingAs($chief);
        $this->expectException(HttpException::class);

        $ris->transitionTo(Requisition::STATUS_RECEIVED);
    }

    public function test_staff_can_receive_own_ris_transition(): void
    {
        $staff = $this->makeUser('staff');
        $ris = $this->makeRequisition($staff->id);
        $ris->update(['status' => Requisition::STATUS_PENDING_RECEIPT]);

        $this->actingAs($staff);

        $ris->transitionTo(Requisition::STATUS_RECEIVED);

        $this->assertSame(Requisition::STATUS_RECEIVED, $ris->fresh()->status);
    }

    public function test_staff_cannot_receive_other_users_ris_transition(): void
    {
        $other = $this->makeUser('staff');
        $staff = $this->makeUser('staff');
        $ris = $this->makeRequisition($other->id);
        $ris->update(['status' => Requisition::STATUS_PENDING_RECEIPT]);

        $this->actingAs($staff);
        $this->expectException(HttpException::class);

        $ris->transitionTo(Requisition::STATUS_RECEIVED);
    }

    public function test_staff_cannot_approve_ris_transition(): void
    {
        $staff = $this->makeUser('staff');
        $ris = $this->makeRequisition($staff->id);

        $this->actingAs($staff);
        $this->expectException(HttpException::class);

        $ris->transitionTo(Requisition::STATUS_APPROVED);
    }

    public function test_staff_cannot_issue_ris_transition(): void
    {
        $staff = $this->makeUser('staff');
        $ris = $this->makeRequisition($staff->id);
        $ris->update(['status' => Requisition::STATUS_APPROVED]);

        $this->actingAs($staff);
        $this->expectException(HttpException::class);

        $ris->transitionTo(Requisition::STATUS_ISSUED);
    }

    public function test_staff_cannot_cancel_ris_transition(): void
    {
        $staff = $this->makeUser('staff');
        $ris = $this->makeRequisition($staff->id);

        $this->actingAs($staff);
        $this->expectException(HttpException::class);

        $ris->transitionTo(Requisition::STATUS_CANCELLED);
    }

    // ─── Other roles unchanged ──────────────────────────────────────

    public function test_other_roles_permissions_unchanged(): void
    {
        // Supply Officer keeps create/edit/issue abilities. They may issue
        // other users' RIS; a Supply Officer's own RIS must be issued by the
        // Administrator (see User::canIssueRequisition).
        $supplyOfficer = $this->makeUser('supply_officer');
        $this->assertTrue($supplyOfficer->can('create', PurchaseRequest::class));
        $this->assertTrue($supplyOfficer->can('create', Requisition::class));
        $this->assertTrue($supplyOfficer->can('issue', $this->makeRequisition($this->makeUser('staff')->id)));
        $this->assertFalse($supplyOfficer->can('issue', $this->makeRequisition($supplyOfficer->id)));

        // Property Custodian may create PR and RIS requests but remains
        // view + print only elsewhere (no PR/RIS update or approval).
        $pc = $this->makeUser('property_custodian');
        $this->assertTrue($pc->can('viewAny', PurchaseRequest::class));
        $this->assertTrue($pc->can('create', PurchaseRequest::class));
        $this->assertTrue($pc->can('create', Requisition::class));

        // Regional Director retains PR approval authority.
        $rd = $this->makeUser('regional_director');
        $this->assertTrue($rd->can('approve', $this->makePurchaseRequest($rd->id)));

        // Admin retains full abilities.
        $admin = $this->makeUser('admin');
        $this->assertTrue($admin->can('create', PurchaseRequest::class));
        $this->assertTrue($admin->can('approve', $this->makePurchaseRequest($admin->id)));
        $this->assertTrue($admin->can('issue', $this->makeRequisition($admin->id)));
    }
}
