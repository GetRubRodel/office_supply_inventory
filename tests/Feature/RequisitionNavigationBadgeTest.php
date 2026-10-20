<?php

namespace Tests\Feature;

use App\Filament\Resources\RequisitionResource;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RequisitionNavigationBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The User model does not implement FilamentUser, so Filament's
        // panel-auth middleware permits access only when APP_ENV=local.
        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mirror RolePermissionSeeder's permission set so the resource's
        // canAccess() (and any sidebar mounts) never hit Spatie's
        // PermissionDoesNotExist.
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
            'admin' => Permission::all()->pluck('name')->all(),
            'division_chief' => ['requisitions.view', 'requisitions.create', 'requisitions.approve', 'requisitions.update', 'requisitions.receive', 'requisitions.print'],
            'staff' => ['requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.receive', 'requisitions.print'],
            'supply_officer' => ['requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.issue', 'requisitions.receive', 'requisitions.print'],
            'regional_director' => ['requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.receive', 'requisitions.print'],
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

    private function makeRequisition(int $userId, string $status): Requisition
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

    // ─── Approvers see the "someone is requesting" badge ─────────────

    public function test_admin_badge_counts_not_yet_issued_requisitions(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');

        $this->makeRequisition($staff->id, Requisition::STATUS_REQUESTED);
        $this->makeRequisition($staff->id, Requisition::STATUS_REQUESTED);
        // Approved but not yet issued — still counts as pending issuance.
        $this->makeRequisition($staff->id, Requisition::STATUS_APPROVED);
        // Already issued — must NOT be counted as pending issuance.
        $this->makeRequisition($staff->id, Requisition::STATUS_ISSUED);

        $this->actingAs($admin);

        $this->assertSame('3', RequisitionResource::getNavigationBadge());
        $this->assertSame('warning', RequisitionResource::getNavigationBadgeColor());
    }

    public function test_division_chief_badge_counts_requested_requisitions(): void
    {
        $chief = $this->makeUser('division_chief');
        $staff = $this->makeUser('staff');

        $this->makeRequisition($staff->id, Requisition::STATUS_REQUESTED);

        $this->actingAs($chief);

        $this->assertSame('1', RequisitionResource::getNavigationBadge());
        $this->assertSame('warning', RequisitionResource::getNavigationBadgeColor());
    }

    public function test_admin_badge_removes_requisition_once_issued(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');

        $approved = $this->makeRequisition($staff->id, Requisition::STATUS_APPROVED);
        $this->makeRequisition($staff->id, Requisition::STATUS_REQUESTED);

        $this->actingAs($admin);

        // Both not-yet-issued requests are pending.
        $this->assertSame('2', RequisitionResource::getNavigationBadge());

        // Issue one of them — it must automatically drop off the count.
        $approved->transitionTo(Requisition::STATUS_ISSUED);

        $this->assertSame('1', RequisitionResource::getNavigationBadge());
    }

    public function test_no_badge_for_approvers_when_nothing_pending(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');

        // Already issued / pending receipt / received / cancelled —
        // nothing is awaiting issuance for the admin badge.
        $this->makeRequisition($staff->id, Requisition::STATUS_ISSUED);
        $this->makeRequisition($staff->id, Requisition::STATUS_PENDING_RECEIPT);
        $this->makeRequisition($staff->id, Requisition::STATUS_RECEIVED);
        $this->makeRequisition($staff->id, Requisition::STATUS_CANCELLED);

        $this->actingAs($admin);

        $this->assertNull(RequisitionResource::getNavigationBadge());
    }

    // ─── Non-approvers never see the requesting badge ────────────────

    public function test_non_approver_roles_do_not_get_requesting_badge(): void
    {
        $supplyOfficer = $this->makeUser('supply_officer');
        $staffUser = $this->makeUser('staff');

        $this->makeRequisition($staffUser->id, Requisition::STATUS_REQUESTED);

        // Supply Officer issues items — no "requesting" badge. Their badge
        // counts only approved RIS awaiting issuance (amber), so a merely
        // requested RIS owned by someone else does not count.
        $this->actingAs($supplyOfficer);
        $this->assertNotSame('1', RequisitionResource::getNavigationBadge());
        $this->assertNull(RequisitionResource::getNavigationBadge());
        $this->assertSame('warning', RequisitionResource::getNavigationBadgeColor());

        // Staff see a badge only for their own issued-but-not-received RIS;
        // a "requested" RIS does not count (items have not been issued yet),
        // so there is no badge here either.
        $this->actingAs($staffUser);
        $this->assertNotSame('1', RequisitionResource::getNavigationBadge());
        $this->assertSame('success', RequisitionResource::getNavigationBadgeColor());
    }

    public function test_staff_badge_counts_only_own_issued_not_received(): void
    {
        $staff = $this->makeUser('staff');
        $other = $this->makeUser('staff');

        // Another user's issued-but-not-received RIS must NOT count for Staff.
        $otherRis = $this->makeRequisition($other->id, Requisition::STATUS_PENDING_RECEIPT);
        $this->addItem($otherRis, 5, 5);

        $this->actingAs($staff);
        $this->assertNull(RequisitionResource::getNavigationBadge());

        // The staff member's own issued-but-not-received RIS now counts.
        $ownRis = $this->makeRequisition($staff->id, Requisition::STATUS_PENDING_RECEIPT);
        $this->addItem($ownRis, 5, 5);

        $this->assertSame('1', RequisitionResource::getNavigationBadge());
        $this->assertSame('success', RequisitionResource::getNavigationBadgeColor());
    }

    public function test_staff_badge_counts_only_issued_statuses(): void
    {
        $staff = $this->makeUser('staff');

        // Not counted: not yet issued (requested/approved), already received,
        // or cancelled.
        $this->makeRequisition($staff->id, Requisition::STATUS_REQUESTED);
        $this->makeRequisition($staff->id, Requisition::STATUS_APPROVED);
        $this->makeRequisition($staff->id, Requisition::STATUS_RECEIVED);
        $this->makeRequisition($staff->id, Requisition::STATUS_CANCELLED);

        $this->actingAs($staff);
        $this->assertNull(RequisitionResource::getNavigationBadge());

        // Counted: issued and pending_receipt (issued, awaiting receipt).
        $this->makeRequisition($staff->id, Requisition::STATUS_ISSUED);
        $this->makeRequisition($staff->id, Requisition::STATUS_PENDING_RECEIPT);

        $this->assertSame('2', RequisitionResource::getNavigationBadge());
        $this->assertSame('success', RequisitionResource::getNavigationBadgeColor());
    }

    public function test_staff_badge_updates_with_issue_and_receive_flow(): void
    {
        $staff = $this->makeUser('staff');
        $supplyOfficer = $this->makeUser('supply_officer');

        $ris = $this->makeRequisition($staff->id, Requisition::STATUS_APPROVED);
        $this->addItem($ris, 5, 0);

        // Not yet issued — no badge for the staff member.
        $this->actingAs($staff);
        $this->assertNull(RequisitionResource::getNavigationBadge());

        // Items are issued → the RIS moves to pending receipt → the badge
        // appears automatically, with no manual badge/database update.
        $this->actingAs($supplyOfficer);
        $ris->transitionTo(Requisition::STATUS_ISSUED);
        $ris->transitionTo(Requisition::STATUS_PENDING_RECEIPT);

        $this->actingAs($staff);
        $this->assertSame('1', RequisitionResource::getNavigationBadge());

        // The same RIS can only ever be issued once (status flow prevents a
        // repeat issue), so it can never be counted twice.
        $this->assertSame('1', RequisitionResource::getNavigationBadge());

        // Staff receives/acknowledges the items → the badge clears
        // automatically.
        $ris->transitionTo(Requisition::STATUS_RECEIVED);

        $this->assertNull(RequisitionResource::getNavigationBadge());
    }

    // ─── Supply Officer: approved RIS awaiting issuance ──────────────

    public function test_supply_officer_badge_counts_approved_ris_pending_issuance(): void
    {
        $so = $this->makeUser('supply_officer');
        $staff = $this->makeUser('staff');

        // Approved by the Administrator/Division Chief and not yet issued.
        $this->makeRequisition($staff->id, Requisition::STATUS_APPROVED);
        $this->makeRequisition($staff->id, Requisition::STATUS_APPROVED);

        $this->actingAs($so);

        $this->assertSame('2', RequisitionResource::getNavigationBadge());
        $this->assertSame('warning', RequisitionResource::getNavigationBadgeColor());
        $this->assertSame('Approved RIS requests awaiting issuance', RequisitionResource::getNavigationBadgeTooltip());
    }

    public function test_supply_officer_badge_ignores_non_approved_ris(): void
    {
        $so = $this->makeUser('supply_officer');
        $staff = $this->makeUser('staff');

        // Only approved RIS await the Supply Officer's issuance action.
        // Requested (not yet approved) and everything past issuance do not
        // count.
        $this->makeRequisition($staff->id, Requisition::STATUS_REQUESTED);
        $this->makeRequisition($staff->id, Requisition::STATUS_ISSUED);
        $this->makeRequisition($staff->id, Requisition::STATUS_PENDING_RECEIPT);
        $this->makeRequisition($staff->id, Requisition::STATUS_RECEIVED);
        $this->makeRequisition($staff->id, Requisition::STATUS_CANCELLED);

        $this->actingAs($so);
        $this->assertNull(RequisitionResource::getNavigationBadge());
    }

    public function test_supply_officer_badge_updates_automatically_with_status_changes(): void
    {
        $so = $this->makeUser('supply_officer');
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');

        $ris = $this->makeRequisition($staff->id, Requisition::STATUS_REQUESTED);

        // Not yet approved — nothing pending the Supply Officer's action.
        $this->actingAs($so);
        $this->assertNull(RequisitionResource::getNavigationBadge());

        // The Administrator approves it → the badge appears automatically.
        $this->actingAs($admin);
        $ris->transitionTo(Requisition::STATUS_APPROVED);

        $this->actingAs($so);
        $this->assertSame('1', RequisitionResource::getNavigationBadge());

        // The Supply Officer issues the items → the badge clears
        // automatically, with no manual badge/database update.
        $ris->transitionTo(Requisition::STATUS_ISSUED);
        $this->assertNull(RequisitionResource::getNavigationBadge());
    }

    public function test_supply_officer_approved_ris_is_never_counted_twice(): void
    {
        $so = $this->makeUser('supply_officer');
        $staff = $this->makeUser('staff');

        // One approved RIS carrying several line items still counts once.
        $ris = $this->makeRequisition($staff->id, Requisition::STATUS_APPROVED);
        $this->addItem($ris, 5, 0);
        $this->addItem($ris, 3, 0);

        $this->actingAs($so);

        $this->assertSame('1', RequisitionResource::getNavigationBadge());
        // Re-reading the badge does not accumulate — the count is derived
        // live from the same single requisition row.
        $this->assertSame('1', RequisitionResource::getNavigationBadge());
    }

    public function test_approved_ris_issuance_badge_is_only_visible_to_supply_officer(): void
    {
        $staff = $this->makeUser('staff');
        $this->makeRequisition($staff->id, Requisition::STATUS_APPROVED);

        // Supply Officer: sees the approved-awaiting-issuance badge.
        $this->actingAs($this->makeUser('supply_officer'));
        $this->assertSame('1', RequisitionResource::getNavigationBadge());
        $this->assertSame('warning', RequisitionResource::getNavigationBadgeColor());

        // Staff: only their own issued-but-not-received RIS count, so an
        // approved RIS does not surface here.
        $this->actingAs($staff);
        $this->assertNull(RequisitionResource::getNavigationBadge());

        // Regional Director: same own-issued-only scope.
        $this->actingAs($this->makeUser('regional_director'));
        $this->assertNull(RequisitionResource::getNavigationBadge());

        // Admin: unchanged workload badge (requested + approved).
        $this->actingAs($this->makeUser('admin'));
        $this->assertSame('1', RequisitionResource::getNavigationBadge());

        // Division Chief: counts requested (none) plus own issued (none).
        $this->actingAs($this->makeUser('division_chief'));
        $this->assertNull(RequisitionResource::getNavigationBadge());
    }

    public function test_supply_officer_scope_counts_only_approved_requisitions(): void
    {
        $staff = $this->makeUser('staff');

        $this->makeRequisition($staff->id, Requisition::STATUS_APPROVED);
        $this->makeRequisition($staff->id, Requisition::STATUS_REQUESTED);
        $this->makeRequisition($staff->id, Requisition::STATUS_RECEIVED);

        // The shared scope is the single source of truth for the badge and
        // any backend query, and matches exclusively on the approved status.
        $this->assertSame(1, Requisition::query()->awaitingIssuance()->count());
    }

    public function test_regional_director_badge_counts_only_own_issued_not_received(): void
    {
        $rd = $this->makeUser('regional_director');
        $other = $this->makeUser('staff');

        // Another user's issued-but-not-received RIS must NOT count for RD.
        $otherRis = $this->makeRequisition($other->id, Requisition::STATUS_PENDING_RECEIPT);
        $this->addItem($otherRis, 5, 5);

        $this->actingAs($rd);
        $this->assertNull(RequisitionResource::getNavigationBadge());

        // The RD's own issued-but-not-received RIS now counts.
        $ownRis = $this->makeRequisition($rd->id, Requisition::STATUS_PENDING_RECEIPT);
        $this->addItem($ownRis, 5, 5);

        $this->assertSame('1', RequisitionResource::getNavigationBadge());
        $this->assertSame('success', RequisitionResource::getNavigationBadgeColor());
    }

    public function test_division_chief_badge_combines_requested_and_own_issued(): void
    {
        $chief = $this->makeUser('division_chief');
        $staff = $this->makeUser('staff');

        // Requested RIS awaiting approval counts.
        $this->makeRequisition($staff->id, Requisition::STATUS_REQUESTED);

        $this->actingAs($chief);
        $this->assertSame('1', RequisitionResource::getNavigationBadge());
        $this->assertSame('warning', RequisitionResource::getNavigationBadgeColor());

        // Chief's own issued-but-not-received RIS also counts.
        $ownRis = $this->makeRequisition($chief->id, Requisition::STATUS_PENDING_RECEIPT);
        $this->addItem($ownRis, 5, 5);

        $this->assertSame('2', RequisitionResource::getNavigationBadge());
        $this->assertSame('warning', RequisitionResource::getNavigationBadgeColor());

        // Receiving clears the chief's own issued count.
        $ownRis->transitionTo(Requisition::STATUS_RECEIVED);
        $this->assertSame('1', RequisitionResource::getNavigationBadge());
    }

    private function addItem(Requisition $ris, int $requested, int $issued): void
    {
        RequisitionItem::create([
            'requisition_id' => $ris->id,
            'quantity_requested' => $requested,
            'quantity_issued' => $issued,
        ]);
    }
}
