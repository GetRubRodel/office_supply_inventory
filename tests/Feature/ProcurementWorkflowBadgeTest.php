<?php

namespace Tests\Feature;

use App\Filament\Resources\AbstractOfCanvassResource;
use App\Filament\Resources\BacResolutionResource;
use App\Filament\Resources\InspectionAcceptanceResource;
use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Resources\RequestForQuotationResource;
use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\User;
use App\Services\ProcurementBadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * End-to-end verification of the sequential Purchase Management workflow
 * badges (PR → RFQ → ABC → BAC Resolution → PO → IAR).
 *
 * Each badge counts only eligible source records waiting for the next
 * procurement stage, is visible only to roles authorized to process that
 * module, and clears automatically once the next document is created.
 */
class ProcurementWorkflowBadgeTest extends TestCase
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

        $rolePermissions = [
            'admin' => Permission::all()->pluck('name')->all(),
            'supply_officer' => [
                'request_for_quotations.view', 'request_for_quotations.create',
                'abstracts_of_canvass.view', 'abstracts_of_canvass.create',
                'bac_resolutions.view', 'bac_resolutions.create',
                'purchase_orders.view', 'purchase_orders.create',
                'inspection_acceptance_reports.view', 'inspection_acceptance_reports.create',
            ],
            'property_custodian' => [
                'request_for_quotations.view', 'request_for_quotations.create',
                'abstracts_of_canvass.view', 'abstracts_of_canvass.create',
                'bac_resolutions.view', 'bac_resolutions.create',
                'purchase_orders.view', 'purchase_orders.create',
                'inspection_acceptance_reports.view', 'inspection_acceptance_reports.create',
            ],
            // View-only roles: they may track the pipeline but cannot process
            // any module, so they must never see the conversion badges.
            'regional_director' => [
                'request_for_quotations.view', 'abstracts_of_canvass.view',
                'bac_resolutions.view', 'purchase_orders.view',
                'inspection_acceptance_reports.view',
            ],
            'staff' => [
                'request_for_quotations.view', 'abstracts_of_canvass.view',
                'bac_resolutions.view', 'purchase_orders.view',
                'inspection_acceptance_reports.view',
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

    // ─── Document chain helpers ─────────────────────────────────────

    private function makePr(string $status = PurchaseRequest::STATUS_APPROVED): PurchaseRequest
    {
        return PurchaseRequest::create([
            'date' => now()->subDays(4),
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
            'date' => now()->subDays(3),
        ]);
    }

    private function makeAbc(RequestForQuotation $rfq): AbstractOfCanvass
    {
        return AbstractOfCanvass::create([
            'rfq_id' => $rfq->id,
            'supplier1_name' => 'Test Supplier Co.',
            'supplier2_name' => 'Other Supplier Inc.',
            'supplier3_name' => 'Third Supplier LLC',
            'recommendation' => 'Test Supplier Co.',
            'date_of_advertisement' => now(),
            'date_of_opening' => now(),
        ]);
    }

    private function makeBac(AbstractOfCanvass $abc): BacResolution
    {
        return BacResolution::create([
            'abc_id' => $abc->id,
            'title' => 'Resolution title',
            'date' => now(),
        ]);
    }

    private function makePo(BacResolution $bac): PurchaseOrder
    {
        return PurchaseOrder::create([
            'bac_resolution_id' => $bac->id,
            'supplier_name' => 'Test Supplier Co.',
            'address' => 'Koronadal City',
            'tin' => '123456789',
            'date' => now(),
            'mode_of_procurement' => 'Public Bidding',
        ]);
    }

    private function makeIar(PurchaseOrder $po): InspectionAcceptanceReport
    {
        return InspectionAcceptanceReport::create([
            'po_id' => $po->id,
            'supplier_name' => 'Test Supplier Co.',
            'po_no' => $po->po_no,
            'date' => now(),
            'requisitioning_office_dept' => 'Administrative Division',
        ]);
    }

    // ─── RFQ badge (approved PRs with no RFQ yet) ───────────────────

    public function test_rfq_badge_counts_approved_prs_without_rfq_for_supply_officer(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $this->makePr(); // approved, no RFQ
        $this->makePr(); // approved, no RFQ

        $this->assertSame('2', RequestForQuotationResource::getNavigationBadge());
        $this->assertSame('warning', RequestForQuotationResource::getNavigationBadgeColor());
    }

    public function test_rfq_badge_ignores_non_approved_prs(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $this->makePr(PurchaseRequest::STATUS_DRAFT);
        $this->makePr(PurchaseRequest::STATUS_FOR_REVIEW);
        $this->makePr(PurchaseRequest::STATUS_REJECTED);
        $this->makePr(PurchaseRequest::STATUS_CANCELLED);

        $this->assertNull(RequestForQuotationResource::getNavigationBadge());
    }

    public function test_rfq_badge_clears_when_rfq_is_created(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $pr = $this->makePr();
        $this->assertSame('1', RequestForQuotationResource::getNavigationBadge());

        $this->makeRfq($pr);

        $this->assertNull(RequestForQuotationResource::getNavigationBadge());
    }

    public function test_rfq_badge_visible_only_to_roles_authorized_to_process_rfq(): void
    {
        $this->makePr();

        // Supply Officer, Admin and Property Custodian process RFQs.
        $this->actingAs($this->makeUser('supply_officer'));
        $this->assertSame('1', RequestForQuotationResource::getNavigationBadge());

        $this->actingAs($this->makeUser('admin'));
        $this->assertSame('1', RequestForQuotationResource::getNavigationBadge());

        $this->actingAs($this->makeUser('property_custodian'));
        $this->assertSame('1', RequestForQuotationResource::getNavigationBadge());

        // View-only roles never see the conversion badge.
        $this->actingAs($this->makeUser('regional_director'));
        $this->assertNull(RequestForQuotationResource::getNavigationBadge());

        $this->actingAs($this->makeUser('staff'));
        $this->assertNull(RequestForQuotationResource::getNavigationBadge());
    }

    // ─── ABC badge (RFQs with no ABC yet) ───────────────────────────

    public function test_abc_badge_counts_rfqs_without_abc(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $this->makeRfq($this->makePr());
        $this->makeRfq($this->makePr());

        $this->assertSame('2', AbstractOfCanvassResource::getNavigationBadge());
    }

    public function test_abc_badge_ignores_cancelled_and_rejected_rfqs(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $cancelled = $this->makeRfq($this->makePr());
        $cancelled->status = RequestForQuotation::STATUS_CANCELLED;
        $cancelled->save();

        $rejected = $this->makeRfq($this->makePr());
        $rejected->status = RequestForQuotation::STATUS_REJECTED;
        $rejected->save();

        $this->assertNull(AbstractOfCanvassResource::getNavigationBadge());
    }

    public function test_abc_badge_clears_when_abc_is_created(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $rfq = $this->makeRfq($this->makePr());
        $this->assertSame('1', AbstractOfCanvassResource::getNavigationBadge());

        $this->makeAbc($rfq);

        $this->assertNull(AbstractOfCanvassResource::getNavigationBadge());
    }

    // ─── BAC Resolution badge (ABCs with no BAC Resolution yet) ─────

    public function test_bac_badge_counts_abcs_without_bac_resolution(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $this->makeAbc($this->makeRfq($this->makePr()));
        $this->makeAbc($this->makeRfq($this->makePr()));

        $this->assertSame('2', BacResolutionResource::getNavigationBadge());
    }

    public function test_bac_badge_ignores_cancelled_and_rejected_abcs(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $cancelled = $this->makeAbc($this->makeRfq($this->makePr()));
        $cancelled->status = AbstractOfCanvass::STATUS_CANCELLED;
        $cancelled->save();

        $rejected = $this->makeAbc($this->makeRfq($this->makePr()));
        $rejected->status = AbstractOfCanvass::STATUS_REJECTED;
        $rejected->save();

        $this->assertNull(BacResolutionResource::getNavigationBadge());
    }

    public function test_bac_badge_clears_when_bac_resolution_is_created(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $abc = $this->makeAbc($this->makeRfq($this->makePr()));
        $this->assertSame('1', BacResolutionResource::getNavigationBadge());

        $this->makeBac($abc);

        $this->assertNull(BacResolutionResource::getNavigationBadge());
    }

    // ─── PO badge (BAC Resolutions with no PO yet) ──────────────────

    public function test_po_badge_counts_bac_resolutions_without_po(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $this->makeBac($this->makeAbc($this->makeRfq($this->makePr())));
        $this->makeBac($this->makeAbc($this->makeRfq($this->makePr())));

        $this->assertSame('2', PurchaseOrderResource::getNavigationBadge());
    }

    public function test_po_badge_ignores_cancelled_and_rejected_bacs(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $cancelled = $this->makeBac($this->makeAbc($this->makeRfq($this->makePr())));
        $cancelled->status = BacResolution::STATUS_CANCELLED;
        $cancelled->save();

        $rejected = $this->makeBac($this->makeAbc($this->makeRfq($this->makePr())));
        $rejected->status = BacResolution::STATUS_REJECTED;
        $rejected->save();

        $this->assertNull(PurchaseOrderResource::getNavigationBadge());
    }

    public function test_po_badge_clears_when_po_is_created(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $bac = $this->makeBac($this->makeAbc($this->makeRfq($this->makePr())));
        $this->assertSame('1', PurchaseOrderResource::getNavigationBadge());

        $this->makePo($bac);

        $this->assertNull(PurchaseOrderResource::getNavigationBadge());
    }

    // ─── IAR badge (POs with no IAR yet) ────────────────────────────

    public function test_iar_badge_counts_pos_without_iar(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $this->makePo($this->makeBac($this->makeAbc($this->makeRfq($this->makePr()))));
        $this->makePo($this->makeBac($this->makeAbc($this->makeRfq($this->makePr()))));

        $this->assertSame('2', InspectionAcceptanceResource::getNavigationBadge());
    }

    public function test_iar_badge_ignores_cancelled_and_rejected_pos(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $cancelled = $this->makePo($this->makeBac($this->makeAbc($this->makeRfq($this->makePr()))));
        $cancelled->status = PurchaseOrder::STATUS_CANCELLED;
        $cancelled->save();

        $rejected = $this->makePo($this->makeBac($this->makeAbc($this->makeRfq($this->makePr()))));
        $rejected->status = PurchaseOrder::STATUS_REJECTED;
        $rejected->save();

        $this->assertNull(InspectionAcceptanceResource::getNavigationBadge());
    }

    public function test_iar_badge_clears_when_iar_is_created(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $po = $this->makePo($this->makeBac($this->makeAbc($this->makeRfq($this->makePr()))));
        $this->assertSame('1', InspectionAcceptanceResource::getNavigationBadge());

        $this->makeIar($po);

        $this->assertNull(InspectionAcceptanceResource::getNavigationBadge());
    }

    // ─── Sequential flow: the badge moves down the pipeline ─────────

    public function test_badge_moves_through_the_full_workflow(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        // Stage 1: approved PR waits for an RFQ.
        $pr = $this->makePr();
        $this->assertSame('1', RequestForQuotationResource::getNavigationBadge());
        $this->assertNull(AbstractOfCanvassResource::getNavigationBadge());

        // Stage 2: RFQ created → RFQ badge clears, ABC badge activates.
        $rfq = $this->makeRfq($pr);
        $this->assertNull(RequestForQuotationResource::getNavigationBadge());
        $this->assertSame('1', AbstractOfCanvassResource::getNavigationBadge());

        // Stage 3: ABC created → ABC badge clears, BAC badge activates.
        $abc = $this->makeAbc($rfq);
        $this->assertNull(AbstractOfCanvassResource::getNavigationBadge());
        $this->assertSame('1', BacResolutionResource::getNavigationBadge());

        // Stage 4: BAC Resolution created → BAC badge clears, PO badge activates.
        $bac = $this->makeBac($abc);
        $this->assertNull(BacResolutionResource::getNavigationBadge());
        $this->assertSame('1', PurchaseOrderResource::getNavigationBadge());

        // Stage 5: PO created → PO badge clears, IAR badge activates.
        $po = $this->makePo($bac);
        $this->assertNull(PurchaseOrderResource::getNavigationBadge());
        $this->assertSame('1', InspectionAcceptanceResource::getNavigationBadge());

        // Stage 6: IAR created → IAR badge clears; the pipeline is complete.
        $this->makeIar($po);
        $this->assertNull(InspectionAcceptanceResource::getNavigationBadge());
    }

    // ─── Duplicate prevention ───────────────────────────────────────

    public function test_converted_source_is_never_counted_twice(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $pr = $this->makePr();
        $this->makeRfq($pr);

        // The PR is converted exactly once — the RFQ badge stays empty even
        // though the RFQ record itself exists.
        $this->assertNull(RequestForQuotationResource::getNavigationBadge());
        $this->assertSame(0, ProcurementBadgeService::count(ProcurementBadgeService::STAGE_RFQ));
    }

    public function test_badge_counts_are_shared_with_backend_eligibility_queries(): void
    {
        $this->actingAs($this->makeUser('supply_officer'));

        $pr = $this->makePr();

        // Navigation badge and the create-page guard agree on the same count.
        $this->assertSame('1', RequestForQuotationResource::getNavigationBadge());
        $this->assertTrue(RequestForQuotationResource::hasEligibleSource());
        $this->assertSame(1, ProcurementBadgeService::count(ProcurementBadgeService::STAGE_RFQ));

        $this->makeRfq($pr);

        $this->assertNull(RequestForQuotationResource::getNavigationBadge());
        $this->assertFalse(RequestForQuotationResource::hasEligibleSource());
        $this->assertSame(0, ProcurementBadgeService::count(ProcurementBadgeService::STAGE_RFQ));
    }
}