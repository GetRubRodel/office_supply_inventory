<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PrPositionFieldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mirror RolePermissionSeeder's permission set (PR-relevant subset).
        $modules = ['dashboard', 'supplies', 'purchase_requests', 'requisitions'];
        foreach ($modules as $module) {
            foreach (['view', 'create', 'update', 'delete', 'approve'] as $action) {
                Permission::findOrCreate("{$module}.{$action}", 'web');
            }
        }
        foreach (['purchase_requests.print', 'requisitions.print', 'requisitions.issue', 'requisitions.receive'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $rolePermissions = [
            'admin' => Permission::all()->pluck('name')->all(),
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

    private function makeUser(string $role, string $position): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'approved',
            'position' => $position,
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
            'status' => PurchaseRequest::STATUS_DRAFT,
        ]);
    }

    public function test_submit_records_requester_position_from_profile(): void
    {
        $staff = $this->makeUser('staff', 'Administrative Aide III');
        $pr = $this->makePurchaseRequest($staff->id);

        $this->actingAs($staff);
        $pr->transitionTo(PurchaseRequest::STATUS_FOR_REVIEW);

        $pr->refresh();

        $this->assertSame($staff->name, $pr->requested_by_name);
        // Position comes from the profile, not the role ('staff').
        $this->assertSame('Administrative Aide III', $pr->requested_by_designation);
        $this->assertNotSame($staff->role, $pr->requested_by_designation);
    }

    public function test_submit_keeps_position_already_saved_from_create_form(): void
    {
        $staff = $this->makeUser('staff', 'Administrative Aide III');
        $pr = $this->makePurchaseRequest($staff->id);
        // Simulate the create form persisting the requester's profile position.
        $pr->update([
            'requested_by_name' => $staff->name,
            'requested_by_designation' => $staff->position,
        ]);

        $this->actingAs($staff);
        $pr->transitionTo(PurchaseRequest::STATUS_FOR_REVIEW);

        $pr->refresh();

        $this->assertSame('Administrative Aide III', $pr->requested_by_designation);
    }

    public function test_approve_records_approver_position_from_profile(): void
    {
        $admin = $this->makeUser('admin', 'OIC Director');
        $staff = $this->makeUser('staff', 'Administrative Aide III');
        $pr = $this->makePurchaseRequest($staff->id);
        $pr->update(['status' => PurchaseRequest::STATUS_FOR_REVIEW]);

        $this->actingAs($admin);
        $pr->transitionTo(PurchaseRequest::STATUS_APPROVED);

        $pr->refresh();

        $this->assertSame($admin->name, $pr->approved_by_name);
        // Position comes from the profile, not the role ('admin').
        $this->assertSame('OIC Director', $pr->approved_by_designation);
        $this->assertNotSame($admin->role, $pr->approved_by_designation);
    }

    public function test_reject_records_rejector_position_from_profile(): void
    {
        $admin = $this->makeUser('admin', 'OIC Director');
        $staff = $this->makeUser('staff', 'Administrative Aide III');
        $pr = $this->makePurchaseRequest($staff->id);
        $pr->update(['status' => PurchaseRequest::STATUS_FOR_REVIEW]);

        $this->actingAs($admin);
        $pr->transitionTo(PurchaseRequest::STATUS_REJECTED, 'Insufficient budget allocation.');

        $pr->refresh();

        $this->assertSame($admin->name, $pr->rejected_by_name);
        // Position comes from the profile, not the role ('admin').
        $this->assertSame('OIC Director', $pr->rejected_by_designation);
        $this->assertNotSame($admin->role, $pr->rejected_by_designation);
    }

    public function test_print_view_has_no_hardcoded_position_fallbacks(): void
    {
        $admin = $this->makeUser('admin', 'OIC Director');
        $staff = $this->makeUser('staff', 'Administrative Aide III');
        $pr = $this->makePurchaseRequest($staff->id);
        $pr->update([
            'requested_by_name' => $staff->name,
            'requested_by_designation' => $staff->position,
            'status' => PurchaseRequest::STATUS_FOR_REVIEW,
        ]);

        $this->actingAs($admin);
        $response = $this->get("/pr/print/{$pr->id}");

        $response->assertOk();
        $response->assertSee('Administrative Aide III', false);
        $response->assertDontSee('Special Investigator III', false);
        $response->assertDontSee('Regional Director', false);
    }
}