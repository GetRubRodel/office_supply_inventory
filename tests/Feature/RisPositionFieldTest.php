<?php

namespace Tests\Feature;

use App\Filament\Resources\RequisitionResource\Pages\CreateRequisition;
use App\Filament\Resources\RequisitionResource\Pages\EditRequisition;
use App\Filament\Resources\RequisitionResource\Pages\ViewRequisition;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The RIS "Requesting Party → Designation" must resolve from the requester's
 * profile `position` column, never from the RBAC `role`, and must be
 * read-only in the Filament form.
 */
class RisPositionFieldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mirror RolePermissionSeeder's permission set (RIS-relevant subset).
        $modules = ['dashboard', 'supplies', 'requisitions'];
        foreach ($modules as $module) {
            foreach (['view', 'create', 'update', 'delete', 'approve'] as $action) {
                Permission::findOrCreate("{$module}.{$action}", 'web');
            }
        }
        foreach (['requisitions.print', 'requisitions.issue', 'requisitions.receive'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $rolePermissions = [
            'admin' => Permission::all()->pluck('name')->all(),
            'staff' => [
                'dashboard.view', 'supplies.view',
                'requisitions.view', 'requisitions.create', 'requisitions.update',
                'requisitions.receive', 'requisitions.print',
            ],
            'division_chief' => [
                'dashboard.view', 'supplies.view',
                'requisitions.view', 'requisitions.create', 'requisitions.approve', 'requisitions.receive',
            ],
        ];

        foreach ($rolePermissions as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }

    private function makeUser(string $role, ?string $position): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'approved',
            'position' => $position,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeRequisition(int $userId): Requisition
    {
        return Requisition::create([
            'user_id' => $userId,
            'entity_name' => 'CHR Region XII',
            'division' => 'Administrative',
            'office' => 'Administrative Division',
            'purpose' => 'Replenish office supplies',
        ]);
    }

    /** Read the stored column, bypassing any accessor. */
    private function storedDesignation(Requisition $requisition): ?string
    {
        return DB::table('requisitions')
            ->where('id', $requisition->id)
            ->value('requested_by_designation');
    }

    public function test_creating_stores_requester_position_from_profile(): void
    {
        $staff = $this->makeUser('staff', 'Administrative Aide III');

        $this->actingAs($staff);
        $requisition = $this->makeRequisition($staff->id);

        $this->assertSame($staff->name, $requisition->requested_by_name);
        // Position comes from the profile, not the role ('staff').
        $this->assertSame('Administrative Aide III', $requisition->requested_by_designation);
        $this->assertNotSame($staff->role, $requisition->requested_by_designation);
        $this->assertSame('Administrative Aide III', $this->storedDesignation($requisition));
    }

    public function test_creating_never_stores_the_role_as_designation(): void
    {
        // A user whose role label would leak into the designation if the
        // model regressed to reading `role`.
        $chief = $this->makeUser('division_chief', 'Attorney III');

        $this->actingAs($chief);
        $requisition = $this->makeRequisition($chief->id);

        $this->assertSame('Attorney III', $requisition->requested_by_designation);
        $this->assertNotSame('division_chief', $requisition->requested_by_designation);
    }

    public function test_creating_uses_position_of_the_requisition_owner(): void
    {
        $staff = $this->makeUser('staff', 'Administrative Aide III');
        $other = $this->makeUser('staff', 'Process Server');

        // An admin creating an RIS on behalf of a staff member: the
        // designation must be the owner's position, not the actor's.
        $admin = $this->makeUser('admin', 'OIC Director');
        $this->actingAs($admin);
        $requisition = $this->makeRequisition($other->id);

        $this->assertSame('Process Server', $requisition->requested_by_designation);

        unset($staff);
    }

    public function test_blank_position_leaves_designation_empty_instead_of_a_role(): void
    {
        $staff = $this->makeUser('staff', null);

        $this->actingAs($staff);
        $requisition = $this->makeRequisition($staff->id);

        // No hardcoded/default designation is invented when the profile has
        // no position on file.
        $this->assertNull($requisition->requested_by_designation);
        $this->assertNull($requisition->requesterPosition());
    }

    public function test_requester_position_reflects_profile_over_stale_snapshot(): void
    {
        $staff = $this->makeUser('staff', 'Administrative Aide III');

        $this->actingAs($staff);
        $requisition = $this->makeRequisition($staff->id);

        // Simulate a legacy row that still holds an RBAC role.
        DB::table('requisitions')
            ->where('id', $requisition->id)
            ->update(['requested_by_designation' => 'staff']);

        $requisition = $requisition->fresh();

        // The live profile position wins, so form/view/print all agree.
        $this->assertSame('Administrative Aide III', $requisition->requesterPosition());
    }

    public function test_requester_position_falls_back_to_snapshot_when_profile_blank(): void
    {
        $staff = $this->makeUser('staff', 'Administrative Aide III');

        $this->actingAs($staff);
        $requisition = $this->makeRequisition($staff->id);

        $staff->update(['position' => null]);
        $requisition = $requisition->fresh();

        $this->assertSame('Administrative Aide III', $requisition->requesterPosition());
    }

    public function test_approve_records_approver_position_from_profile(): void
    {
        $admin = $this->makeUser('admin', 'OIC Director');
        $staff = $this->makeUser('staff', 'Administrative Aide III');

        $this->actingAs($staff);
        $requisition = $this->makeRequisition($staff->id);

        $this->actingAs($admin);
        $requisition->transitionTo(Requisition::STATUS_APPROVED);

        $requisition->refresh();

        $this->assertSame($admin->name, $requisition->approved_by_name);
        $this->assertSame('OIC Director', $requisition->approved_by_designation);
        $this->assertNotSame($admin->role, $requisition->approved_by_designation);
    }

    public function test_issue_records_issuer_position_from_profile(): void
    {
        $admin = $this->makeUser('admin', 'OIC Director');
        $staff = $this->makeUser('staff', 'Administrative Aide III');

        $this->actingAs($staff);
        $requisition = $this->makeRequisition($staff->id);

        $this->actingAs($admin);
        $requisition->transitionTo(Requisition::STATUS_APPROVED);
        $requisition->transitionTo(Requisition::STATUS_ISSUED);

        $requisition->refresh();

        $this->assertSame('OIC Director', $requisition->issued_by_designation);
        $this->assertNotSame('admin', $requisition->issued_by_designation);
    }

    public function test_receive_records_receiver_position_from_profile(): void
    {
        $admin = $this->makeUser('admin', 'OIC Director');
        $staff = $this->makeUser('staff', 'Administrative Aide III');

        $this->actingAs($staff);
        $requisition = $this->makeRequisition($staff->id);

        $this->actingAs($admin);
        $requisition->transitionTo(Requisition::STATUS_APPROVED);
        $requisition->transitionTo(Requisition::STATUS_ISSUED);
        $requisition->transitionTo(Requisition::STATUS_PENDING_RECEIPT);

        $this->actingAs($staff);
        $requisition->transitionTo(Requisition::STATUS_RECEIVED);

        $requisition->refresh();

        $this->assertSame('Administrative Aide III', $requisition->received_by_designation);
        $this->assertNotSame('staff', $requisition->received_by_designation);
    }

    public function test_print_view_shows_position_and_no_role_or_hardcoded_designation(): void
    {
        $admin = $this->makeUser('admin', 'OIC Director');
        $staff = $this->makeUser('staff', 'Administrative Aide III');

        $this->actingAs($staff);
        $requisition = $this->makeRequisition($staff->id);

        $this->actingAs($admin);
        $response = $this->get("/ris/print/{$requisition->id}");

        $response->assertOk();
        $response->assertSee('Administrative Aide III', false);
        // No role string and no default/hardcoded designation leaks out.
        $response->assertDontSee('>staff<', false);
        $response->assertDontSee('Administrative Officer', false);
    }

    public function test_print_view_ignores_legacy_role_snapshot(): void
    {
        $admin = $this->makeUser('admin', 'OIC Director');
        $staff = $this->makeUser('staff', 'Administrative Aide III');

        $this->actingAs($staff);
        $requisition = $this->makeRequisition($staff->id);

        // Legacy row holding the RBAC role in the designation column.
        DB::table('requisitions')
            ->where('id', $requisition->id)
            ->update(['requested_by_designation' => 'staff']);

        $this->actingAs($admin);
        $response = $this->get("/ris/print/{$requisition->id}");

        $response->assertOk();
        $response->assertSee('Administrative Aide III', false);
        $response->assertDontSee('>staff<', false);
    }

    public function test_create_form_prefills_and_locks_the_designation(): void
    {
        $staff = $this->makeUser('staff', 'Administrative Aide III');

        Livewire::actingAs($staff)
            ->test(CreateRequisition::class)
            // The field is prefilled from the profile and rendered read-only.
            ->assertFormFieldIsDisabled('requested_by_designation')
            ->assertFormSet(fn (array $state): array => [
                'requested_by_designation' => 'Administrative Aide III',
                'requested_by_name' => $staff->name,
            ]);
    }

    public function test_create_form_ignores_a_tampered_designation_payload(): void
    {
        $staff = $this->makeUser('staff', 'Administrative Aide III');

        Livewire::actingAs($staff)
            ->test(CreateRequisition::class)
            ->fillForm([
                'fund_cluster' => 'GAA',
                'division' => 'Administrative',
                'office' => 'Administrative Division',
                'purpose' => 'Verify a hand-typed designation cannot be persisted',
                // A crafted payload trying to store a manual designation.
                'requested_by_designation' => 'Supply Officer II',
                'items' => [],
            ])
            ->call('create');

        $stored = $this->storedDesignation(Requisition::latest('id')->first());

        // The resolved profile position wins regardless of the posted value.
        $this->assertSame('Administrative Aide III', $stored);
    }

    public function test_edit_form_displays_live_position_over_stale_snapshot(): void
    {
        $admin = $this->makeUser('admin', 'OIC Director');
        $staff = $this->makeUser('staff', 'Administrative Aide III');

        $this->actingAs($staff);
        $requisition = $this->makeRequisition($staff->id);

        // Legacy row still holding the RBAC role.
        DB::table('requisitions')
            ->where('id', $requisition->id)
            ->update(['requested_by_designation' => 'staff']);

        // The requester updates their plantilla position.
        $staff->update(['position' => 'Administrative Aide IV']);

        Livewire::actingAs($admin)
            ->test(EditRequisition::class, ['record' => $requisition->getRouteKey()])
            ->assertFormFieldIsDisabled('requested_by_designation')
            ->assertFormSet(fn (array $state): array => [
                'requested_by_designation' => 'Administrative Aide IV',
            ]);
    }

    public function test_view_page_displays_the_position_read_only(): void
    {
        $admin = $this->makeUser('admin', 'OIC Director');
        $staff = $this->makeUser('staff', 'Administrative Aide III');

        $this->actingAs($staff);
        $requisition = $this->makeRequisition($staff->id);

        Livewire::actingAs($admin)
            ->test(ViewRequisition::class, ['record' => $requisition->getRouteKey()])
            ->assertFormFieldIsDisabled('requested_by_designation')
            ->assertFormSet(fn (array $state): array => [
                'requested_by_designation' => 'Administrative Aide III',
            ]);
    }
}
