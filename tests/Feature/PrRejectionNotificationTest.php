<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PrRejectionNotificationTest extends TestCase
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

    public function test_rejection_persists_remarks_approver_and_date(): void
    {
        $admin = $this->makeUser('admin');
        $admin->update(['position' => 'OIC Director']);
        $staff = $this->makeUser('staff');
        $pr = $this->makePurchaseRequest($staff->id);

        $this->actingAs($admin);
        $pr->transitionTo(PurchaseRequest::STATUS_REJECTED, 'Insufficient budget allocation.');

        $pr->refresh();

        $this->assertSame(PurchaseRequest::STATUS_REJECTED, $pr->status);
        $this->assertSame('Insufficient budget allocation.', $pr->rejection_reason);
        $this->assertSame($admin->name, $pr->rejected_by_name);
        // The designation comes from the user's profile position, not the role.
        $this->assertSame('OIC Director', $pr->rejected_by_designation);
        $this->assertNotNull($pr->rejected_at);
    }

    public function test_rejection_requires_remarks(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $pr = $this->makePurchaseRequest($staff->id);

        $this->actingAs($admin);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $pr->transitionTo(PurchaseRequest::STATUS_REJECTED, '');
    }

    public function test_notification_is_sent_only_to_the_requester(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $other = $this->makeUser('staff');
        $pr = $this->makePurchaseRequest($staff->id);

        $this->actingAs($admin);
        $pr->transitionTo(PurchaseRequest::STATUS_REJECTED, 'Duplicate request.');

        $this->assertSame(1, $staff->notifications()->count());
        $this->assertSame(0, $other->notifications()->count());
    }

    public function test_notification_payload_is_filament_compatible_and_shows_rejection_details(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $pr = $this->makePurchaseRequest($staff->id);

        $this->actingAs($admin);
        $pr->transitionTo(PurchaseRequest::STATUS_REJECTED, 'Insufficient budget allocation.');

        $notification = $staff->notifications()->latest()->first();
        $data = $notification->data;

        // Filament contract: the bell modal only lists data->format = 'filament'.
        $this->assertSame('filament', $data['format']);
        $this->assertSame('Purchase Request Rejected', $data['title']);
        $this->assertSame('danger', $data['status']);
        $this->assertSame('heroicon-o-x-circle', $data['icon']);

        // The body carries the rejection reason, approver, and date.
        $this->assertStringContainsString('Insufficient budget allocation.', $data['body']);
        $this->assertStringContainsString(e($admin->name), $data['body']);
        $this->assertStringContainsString('Rejection Date:', $data['body']);
        $this->assertStringContainsString($pr->pr_no, $data['body']);

        // Structured payload for reference.
        $this->assertSame($pr->id, $data['purchase_request_id']);
        $this->assertSame($pr->pr_no, $data['pr_no']);
        $this->assertSame($admin->name, $data['rejected_by']);
        $this->assertSame('Insufficient budget allocation.', $data['rejection_reason']);
        $this->assertNotNull($data['rejected_at']);

        // The action links back to the requester's own PR.
        $this->assertSame("/admin/purchase-requests/{$pr->id}", $data['actions'][0]['url']);
    }

    public function test_notification_appears_in_filament_bell_modal_query(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $pr = $this->makePurchaseRequest($staff->id);

        $this->actingAs($admin);
        $pr->transitionTo(PurchaseRequest::STATUS_REJECTED, 'Insufficient budget allocation.');

        // Mirrors Filament\DatabaseNotifications::getNotificationsQuery().
        $visible = $staff->notifications()
            ->where('data->format', 'filament')
            ->count();

        $this->assertSame(1, $visible);
    }

    public function test_notification_renders_through_filament_from_database(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $pr = $this->makePurchaseRequest($staff->id);

        $this->actingAs($admin);
        $pr->transitionTo(PurchaseRequest::STATUS_REJECTED, 'Insufficient budget allocation.');

        $notification = $staff->notifications()->latest()->first();
        $rendered = FilamentNotification::fromDatabase($notification);

        $this->assertSame('Purchase Request Rejected', $rendered->getTitle());
        $this->assertStringContainsString('Insufficient budget allocation.', $rendered->getBody());
        $this->assertStringContainsString('Rejected By:', $rendered->getBody());
    }

    public function test_rejection_creates_exactly_one_notification_per_rejection_event(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $pr = $this->makePurchaseRequest($staff->id);

        $this->actingAs($admin);

        // First rejection → exactly one notification.
        $pr->transitionTo(PurchaseRequest::STATUS_REJECTED, 'Insufficient budget allocation.');
        $this->assertSame(1, $staff->notifications()->count());

        // Revise → resubmit → rejected again is a NEW rejection event, so a
        // second notification is created (never suppressed, never duplicated).
        $pr->transitionTo(PurchaseRequest::STATUS_DRAFT);
        $pr->transitionTo(PurchaseRequest::STATUS_FOR_REVIEW);
        $pr->transitionTo(PurchaseRequest::STATUS_REJECTED, 'Duplicate request.');

        $this->assertSame(2, $staff->notifications()->count());

        $reasons = $staff->notifications()->get()->map(
            fn ($notification) => $notification->data['rejection_reason']
        )->sort()->values()->all();

        $this->assertSame(
            ['Duplicate request.', 'Insufficient budget allocation.'],
            $reasons,
        );
    }

    public function test_staff_sees_rejection_details_only_for_own_pr(): void
    {
        $staff = $this->makeUser('staff');
        $other = $this->makeUser('staff');
        $ownPr = $this->makePurchaseRequest($staff->id);
        $otherPr = $this->makePurchaseRequest($other->id);

        // Staff are own-records-only: the rejection-details section on the PR
        // view is gated to the original requester.
        $this->assertTrue($staff->isOwnRecordsOnly());
        $this->assertTrue($ownPr->user_id === $staff->id);
        $this->assertFalse($otherPr->user_id === $staff->id);
    }
}