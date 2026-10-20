<?php

namespace Tests\Feature;

use App\Models\Requisition;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RisCancellationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mirror RolePermissionSeeder's permission set (RIS-relevant subset).
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
            'division_chief' => [
                'dashboard.view', 'supplies.view',
                'requisitions.view', 'requisitions.create', 'requisitions.approve',
                'requisitions.update', 'requisitions.receive', 'requisitions.print',
            ],
            'staff' => [
                'dashboard.view', 'supplies.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.print',
                'requisitions.view', 'requisitions.create', 'requisitions.update',
                'requisitions.receive', 'requisitions.print',
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

    // ─── Cancellation audit trail ───────────────────────────────────

    public function test_cancellation_persists_remarks_canceller_and_date(): void
    {
        $admin = $this->makeUser('admin');
        $admin->update(['position' => 'OIC Director']);
        $staff = $this->makeUser('staff');
        $ris = $this->makeRequisition($staff->id);

        $this->actingAs($admin);
        $ris->transitionTo(Requisition::STATUS_CANCELLED, 'Request is no longer needed.');

        $ris->refresh();

        $this->assertSame(Requisition::STATUS_CANCELLED, $ris->status);
        $this->assertSame('Request is no longer needed.', $ris->cancellation_remarks);
        $this->assertSame($admin->name, $ris->cancelled_by_name);
        // The designation comes from the user's profile position, not the role.
        $this->assertSame('OIC Director', $ris->cancelled_by_designation);
        $this->assertNotNull($ris->cancelled_by_date);
    }

    // ─── Requester-only delivery ────────────────────────────────────

    public function test_notification_is_sent_only_to_the_requester(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $other = $this->makeUser('staff');
        $chief = $this->makeUser('division_chief');
        $ris = $this->makeRequisition($staff->id);

        $this->actingAs($admin);
        $ris->transitionTo(Requisition::STATUS_CANCELLED, 'Duplicate request.');

        // Original requester gets exactly one notification.
        $this->assertSame(1, $staff->notifications()->count());

        // No one else — other staff, division chiefs, and the canceller
        // themselves never receive the requester-specific notification.
        $this->assertSame(0, $other->notifications()->count());
        $this->assertSame(0, $chief->notifications()->count());
        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_division_chief_cancellation_notifies_requester(): void
    {
        $chief = $this->makeUser('division_chief');
        $chief->update(['position' => 'Chief, Administrative Division']);
        $staff = $this->makeUser('staff');
        $ris = $this->makeRequisition($staff->id);

        $this->actingAs($chief);
        $ris->transitionTo(Requisition::STATUS_CANCELLED, 'Insufficient supplies in stock.');

        $this->assertSame(1, $staff->notifications()->count());

        $data = $staff->notifications()->latest()->first()->data;
        $this->assertSame('filament', $data['format']);
        $this->assertSame($chief->name, $data['cancelled_by']);
        $this->assertSame('Insufficient supplies in stock.', $data['cancellation_remarks']);
    }

    // ─── Bell payload contract ──────────────────────────────────────

    public function test_notification_payload_is_filament_compatible_and_shows_cancellation_details(): void
    {
        $admin = $this->makeUser('admin');
        $admin->update(['position' => 'OIC Director']);
        $staff = $this->makeUser('staff');
        $ris = $this->makeRequisition($staff->id);

        $this->actingAs($admin);
        $ris->transitionTo(Requisition::STATUS_CANCELLED, 'Request is no longer needed.');

        $notification = $staff->notifications()->latest()->first();
        $data = $notification->data;

        // Filament contract: the bell modal only lists data->format = 'filament'.
        $this->assertSame('filament', $data['format']);
        $this->assertSame('Requisition Cancelled', $data['title']);
        $this->assertSame('danger', $data['status']);
        $this->assertSame('heroicon-o-x-circle', $data['icon']);

        // The body carries the Cancelled status, RIS number, remarks,
        // cancelling user (with designation), and cancellation date.
        $this->assertStringContainsString('<strong>Status:</strong> Cancelled', $data['body']);
        $this->assertStringContainsString($ris->ris_no, $data['body']);
        $this->assertStringContainsString('Request is no longer needed.', $data['body']);
        $this->assertStringContainsString(e($admin->name), $data['body']);
        $this->assertStringContainsString('OIC Director', $data['body']);
        $this->assertStringContainsString('Cancellation Date:', $data['body']);

        // Structured payload for reference.
        $this->assertSame($ris->id, $data['requisition_id']);
        $this->assertSame($ris->ris_no, $data['ris_no']);
        $this->assertSame(Requisition::STATUS_CANCELLED, $data['requisition_status']);
        $this->assertSame('Cancelled', $data['status_label']);
        $this->assertSame($admin->name, $data['cancelled_by']);
        $this->assertSame('OIC Director', $data['cancelled_by_designation']);
        $this->assertSame('Request is no longer needed.', $data['cancellation_remarks']);
        $this->assertNotNull($data['cancelled_at']);

        // The action links back to the requester's own RIS and marks the
        // notification as read, clearing the bell badge on open.
        $this->assertSame("/admin/requisitions/{$ris->id}", $data['actions'][0]['url']);
        $this->assertTrue($data['actions'][0]['shouldMarkAsRead']);
    }

    public function test_notification_appears_in_filament_bell_modal_query(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $ris = $this->makeRequisition($staff->id);

        $this->actingAs($admin);
        $ris->transitionTo(Requisition::STATUS_CANCELLED, 'Duplicate request.');

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
        $ris = $this->makeRequisition($staff->id);

        $this->actingAs($admin);
        $ris->transitionTo(Requisition::STATUS_CANCELLED, 'Request is no longer needed.');

        $notification = $staff->notifications()->latest()->first();
        $rendered = FilamentNotification::fromDatabase($notification);

        $this->assertSame('Requisition Cancelled', $rendered->getTitle());
        $this->assertStringContainsString('Request is no longer needed.', $rendered->getBody());
        $this->assertStringContainsString('Cancelled By:', $rendered->getBody());
        $this->assertStringContainsString('Status:', $rendered->getBody());
    }

    // ─── Bell badge lifecycle ───────────────────────────────────────

    public function test_bell_badge_counts_unread_and_clears_when_marked_read(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $ris = $this->makeRequisition($staff->id);

        $this->actingAs($admin);
        $ris->transitionTo(Requisition::STATUS_CANCELLED, 'Request is no longer needed.');

        // The bell badge mirrors Filament's unread count for filament-format
        // notifications — one unread cancellation → badge shows 1.
        $unreadCount = fn (): int => $staff->notifications()
            ->where('data->format', 'filament')
            ->unread()
            ->count();

        $this->assertSame(1, $unreadCount());

        // Opening the notification (View RIS action) or marking it read
        // clears the badge automatically.
        $staff->notifications()->first()->markAsRead();

        $this->assertSame(0, $unreadCount());
    }

    // ─── Duplicate suppression ──────────────────────────────────────

    public function test_cancellation_creates_exactly_one_notification_per_cancellation_event(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $ris = $this->makeRequisition($staff->id);

        $this->actingAs($admin);

        // First (and only possible) cancellation → exactly one notification.
        $ris->transitionTo(Requisition::STATUS_CANCELLED, 'Request is no longer needed.');
        $this->assertSame(1, $staff->notifications()->count());

        // Re-firing the notification dispatch for the same cancellation event
        // is suppressed by the dedupe guard.
        $method = new \ReflectionMethod(Requisition::class, 'notifyRequesterOfCancellation');
        $method->invoke($ris);
        $this->assertSame(1, $staff->notifications()->count());

        // `cancelled` is terminal, so a second cancel attempt can never
        // transition (and therefore can never notify) again.
        try {
            $ris->transitionTo(Requisition::STATUS_CANCELLED, 'Request is no longer needed.');
        } catch (\InvalidArgumentException) {
            // Expected: no further transitions out of cancelled.
        }

        $this->assertSame(1, $staff->notifications()->count());
    }

    public function test_cancellation_without_remarks_still_notifies_requester(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $ris = $this->makeRequisition($staff->id);

        $this->actingAs($admin);
        $ris->transitionTo(Requisition::STATUS_CANCELLED);

        $this->assertSame(1, $staff->notifications()->count());

        $data = $staff->notifications()->latest()->first()->data;

        // The remarks line is always present so the notification contract
        // holds even when no reason was captured.
        $this->assertStringContainsString('<strong>Remarks:</strong> Not provided', $data['body']);
        $this->assertSame('', $data['cancellation_remarks']);
        $this->assertStringContainsString('<strong>Status:</strong> Cancelled', $data['body']);
    }
}
