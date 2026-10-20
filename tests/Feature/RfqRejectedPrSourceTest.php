<?php

namespace Tests\Feature;

use App\Filament\Resources\RequestForQuotationResource;
use App\Filament\Resources\RequestForQuotationResource\Pages\CreateRequestForQuotation;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\User;
use App\Services\ProcurementBadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Rejected (and otherwise invalid) Purchase Requests must never be eligible
 * RFQ sources. The same filtering is enforced everywhere a PR can become an
 * RFQ source:
 *
 *  - the Filament Source PR select (options),
 *  - the RFQ navigation badge (count),
 *  - the backend guard on direct model creation / API access,
 *  - and the create-page eligibility check.
 *
 * A PR that was previously eligible and is later rejected must drop out of
 * the source list and badge count automatically, while the existing
 * PR → RFQ → ABC → BAC Resolution → PO → IAR workflow is preserved.
 */
class RfqRejectedPrSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The User model does not implement FilamentUser, so Filament's
        // panel-auth middleware only permits access when APP_ENV=local.
        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mirror RolePermissionSeeder's permission set so Spatie never hits
        // PermissionDoesNotExist while resolving the badge gates.
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

        Role::findOrCreate('admin', 'web')->syncPermissions(Permission::all()->pluck('name')->all());
        Role::findOrCreate('supply_officer', 'web')->syncPermissions([
            'request_for_quotations.view', 'request_for_quotations.create',
        ]);
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

    private function makePr(string $status = PurchaseRequest::STATUS_APPROVED): PurchaseRequest
    {
        return PurchaseRequest::create([
            'date' => now()->subDays(2),
            'office_division' => 'Administrative Division',
            'name_of_project' => 'Supply of Office Materials',
            'purpose' => 'Replenish office materials',
            'source_of_fund' => 'GAA',
            'status' => $status,
        ]);
    }

    private function makeRfq(PurchaseRequest $pr): RequestForQuotation
    {
        return RequestForQuotation::create([
            'purchase_request_id' => $pr->id,
            'date' => now()->subDays(1),
        ]);
    }

    // ─── Source PR select (Filament form) ──────────────────────────

    public function test_rejected_pr_is_not_offered_in_rfq_source_select(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $approved = $this->makePr(PurchaseRequest::STATUS_APPROVED);
        $rejected = $this->makePr(PurchaseRequest::STATUS_REJECTED);

        $component = Livewire::test(CreateRequestForQuotation::class);

        $values = array_column(
            $component->instance()->form->getSelectOptions('data.purchase_request_id'),
            'value',
        );

        $this->assertContains((string) $approved->id, $values);
        $this->assertNotContains((string) $rejected->id, $values);
    }

    public function test_previously_eligible_pr_rejected_is_removed_from_source_select(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $pr = $this->makePr(PurchaseRequest::STATUS_APPROVED);

        $component = Livewire::test(CreateRequestForQuotation::class);

        $values = array_column(
            $component->instance()->form->getSelectOptions('data.purchase_request_id'),
            'value',
        );
        $this->assertContains((string) $pr->id, $values);

        // The PR is rejected after it was already eligible — it must drop out
        // of the source list automatically (no cache, no manual removal).
        $pr->update(['status' => PurchaseRequest::STATUS_REJECTED]);

        $values = array_column(
            $component->instance()->form->getSelectOptions('data.purchase_request_id'),
            'value',
        );
        $this->assertNotContains((string) $pr->id, $values);
    }

    // ─── RFQ badge ─────────────────────────────────────────────────

    public function test_previously_eligible_pr_rejected_clears_rfq_badge(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $pr = $this->makePr(PurchaseRequest::STATUS_APPROVED);

        $this->assertSame('1', RequestForQuotationResource::getNavigationBadge());
        $this->assertTrue(RequestForQuotationResource::hasEligibleSource());
        $this->assertSame(1, ProcurementBadgeService::count(ProcurementBadgeService::STAGE_RFQ));

        // Reject the previously eligible PR (simulates Admin/RD rejection).
        $pr->update(['status' => PurchaseRequest::STATUS_REJECTED]);

        $this->assertNull(RequestForQuotationResource::getNavigationBadge());
        $this->assertFalse(RequestForQuotationResource::hasEligibleSource());
        $this->assertSame(0, ProcurementBadgeService::count(ProcurementBadgeService::STAGE_RFQ));
    }

    public function test_rfq_badge_ignores_all_invalid_pr_statuses(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $this->makePr(PurchaseRequest::STATUS_DRAFT);
        $this->makePr(PurchaseRequest::STATUS_FOR_REVIEW);
        $this->makePr(PurchaseRequest::STATUS_REJECTED);
        $this->makePr(PurchaseRequest::STATUS_CANCELLED);

        $this->assertNull(RequestForQuotationResource::getNavigationBadge());
        $this->assertSame(0, PurchaseRequest::query()->eligibleForRfq()->count());
    }

    // ─── Backend guard (direct requests / API access) ──────────────

    public function test_rejected_pr_cannot_create_rfq_directly(): void
    {
        $rejected = $this->makePr(PurchaseRequest::STATUS_REJECTED);

        $this->expectException(ValidationException::class);

        $this->makeRfq($rejected);
    }

    public function test_cancelled_pr_cannot_create_rfq_directly(): void
    {
        $cancelled = $this->makePr(PurchaseRequest::STATUS_CANCELLED);

        $this->expectException(ValidationException::class);

        $this->makeRfq($cancelled);
    }

    public function test_rejected_pr_cannot_create_rfq_via_filament_form(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        // An approved PR keeps the create page mountable; the crafted
        // submission then targets the rejected PR.
        $this->makePr(PurchaseRequest::STATUS_APPROVED);
        $rejected = $this->makePr(PurchaseRequest::STATUS_REJECTED);

        Livewire::test(CreateRequestForQuotation::class)
            ->fillForm([
                'purchase_request_id' => $rejected->id,
                'date' => now()->toDateString(),
                'canvassed_by_name' => 'Rodel C. Linugao',
                'canvassed_by_designation' => 'Administrative Assistant II',
            ])
            ->call('create')
            ->assertHasErrors(['purchase_request_id']);

        // The rejected PR must never be converted, even via a crafted request.
        $this->assertSame(0, RequestForQuotation::count());
    }

    // ─── Workflow preservation ─────────────────────────────────────

    public function test_reapproved_pr_becomes_eligible_again_when_never_converted(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);

        $pr = $this->makePr(PurchaseRequest::STATUS_FOR_REVIEW);

        // Rejected → revised to draft → resubmitted → approved again.
        $pr->transitionTo(PurchaseRequest::STATUS_REJECTED, 'Insufficient budget allocation.');
        $pr->transitionTo(PurchaseRequest::STATUS_DRAFT);
        $pr->transitionTo(PurchaseRequest::STATUS_FOR_REVIEW);
        $pr->transitionTo(PurchaseRequest::STATUS_APPROVED);

        $this->assertSame(PurchaseRequest::STATUS_APPROVED, $pr->fresh()->status);
        $this->assertTrue(PurchaseRequest::query()->eligibleForRfq()->whereKey($pr->id)->exists());
    }

    public function test_converted_pr_is_never_re_eligible_even_after_cancellation(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);

        $pr = $this->makePr(PurchaseRequest::STATUS_APPROVED);
        $this->makeRfq($pr);

        // The PR is cancelled after conversion — it must not re-enter the
        // RFQ source pool because it was already converted.
        $pr->transitionTo(PurchaseRequest::STATUS_CANCELLED);

        $this->assertFalse(PurchaseRequest::query()->eligibleForRfq()->whereKey($pr->id)->exists());
        $this->assertNull(RequestForQuotationResource::getNavigationBadge());
    }
}