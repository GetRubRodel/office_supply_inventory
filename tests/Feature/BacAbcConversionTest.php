<?php

namespace Tests\Feature;

use App\Filament\Resources\BacResolutionResource;
use App\Models\AbcItem;
use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * End-to-end verification of the BAC Resolution ← Abstract of Bids and
 * Canvass (ABC) conversion workflow:
 *
 *   1. A BAC Resolution must be created from an eligible (unconverted) ABC.
 *   2. On creation, the BAC auto-hydrates a snapshot of the ABC (ABC no.,
 *      winning supplier, per-item breakdown with winning bid prices).
 *   3. The source ABC becomes ineligible for further conversions.
 *   4. Duplicate conversion is blocked at the model and database levels.
 */
class BacAbcConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The User model does not implement FilamentUser, so Filament's
        // panel-auth middleware only permits access when APP_ENV=local.
        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

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
            'admin' => ['abstracts_of_canvass.view', 'bac_resolutions.view', 'bac_resolutions.create', 'bac_resolutions.update', 'bac_resolutions.delete'],
            'supply_officer' => ['abstracts_of_canvass.view', 'bac_resolutions.view', 'bac_resolutions.create', 'bac_resolutions.update', 'bac_resolutions.delete'],
        ];
        foreach ($rolePermissions as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'approved',
        ]);
        $user->assignRole('admin');

        return $user;
    }

    /**
     * Create an ABC with three canvassed suppliers and the given items.
     * The recommendation points at supplier 1, whose prices win.
     *
     * An ABC must originate from an existing, unconverted Request for
     * Quotation, which in turn must originate from an approved Purchase
     * Request — so the full PR -> RFQ -> ABC chain is built first.
     */
    private function makeAbc(array $itemSpecs = [
        ['item_number' => 1, 'description' => 'Bond Paper', 'unit' => 'Ream', 'quantity' => 10, 'supplier1_price' => 220.00, 'supplier2_price' => 230.00, 'supplier3_price' => 240.00],
        ['item_number' => 2, 'description' => 'Ballpen', 'unit' => 'Piece', 'quantity' => 50, 'supplier1_price' => 15.50, 'supplier2_price' => 16.00, 'supplier3_price' => 16.50],
    ]): AbstractOfCanvass
    {
        $pr = PurchaseRequest::create([
            'date' => now()->subDays(4),
            'office_division' => 'Administrative Division',
            'name_of_project' => 'Supply of Office Materials',
            'purpose' => 'Replenish office materials',
            'source_of_fund' => 'GAA',
            'status' => PurchaseRequest::STATUS_APPROVED,
        ]);

        $rfq = RequestForQuotation::create([
            'purchase_request_id' => $pr->id,
            'date' => now()->subDays(3),
        ]);

        $abc = AbstractOfCanvass::create([
            'rfq_id' => $rfq->id,
            'supplier1_name' => 'Test Supplier Co.',
            'supplier2_name' => 'Other Supplier Inc.',
            'supplier3_name' => 'Third Supplier LLC',
            'recommendation' => 'Test Supplier Co.',
            'date_of_advertisement' => now(),
            'date_of_opening' => now(),
            'status' => 'draft',
        ]);

        foreach ($itemSpecs as $spec) {
            AbcItem::create(['abc_id' => $abc->id] + $spec);
        }

        return $abc->fresh();
    }

    public function test_bac_creation_from_abc_hydrates_snapshot(): void
    {
        $abc = $this->makeAbc();

        $bac = BacResolution::create([
            'abc_id' => $abc->id,
            'title' => 'AUTHORIZING THE CHR XII TO PROCURE',
            'date' => now(),
            'place' => 'Koronadal City, Philippines',
        ]);

        // Auto-generated resolution number in BAC RESO-YYYY-NNNN format.
        $this->assertMatchesRegularExpression('/^BAC RESO-\d{4}-\d{4}$/', $bac->resolution_no);

        // Snapshot hydrated from the source ABC.
        $this->assertEquals($abc->abc_no, $bac->abc_no);
        $this->assertEquals('Test Supplier Co.', $bac->supplier_name);

        // Item snapshot: 2 items, winning (supplier 1) prices, computed totals.
        $this->assertCount(2, $bac->items_snapshot);
        $this->assertEquals('Bond Paper', $bac->items_snapshot[0]['description']);
        $this->assertEquals(10, $bac->items_snapshot[0]['quantity']);
        $this->assertEquals(220.00, (float) $bac->items_snapshot[0]['unit_cost']);
        $this->assertEquals(2200.00, (float) $bac->items_snapshot[0]['total']);

        // Total = (10 x 220) + (50 x 15.50) = 2200 + 775 = 2975.00
        $this->assertEquals(2975.00, $bac->getTotalAmount());
    }

    public function test_converted_abc_is_no_longer_eligible(): void
    {
        $abc = $this->makeAbc();

        $this->assertFalse($abc->isConvertedToBac());
        $this->assertTrue(AbstractOfCanvass::eligibleForBacConversion()->whereKey($abc->id)->exists());
        $this->assertTrue(BacResolutionResource::hasEligibleSource());

        BacResolution::create([
            'abc_id' => $abc->id,
            'title' => 'Resolution title',
            'date' => now(),
        ]);

        $abc->refresh();
        $this->assertTrue($abc->isConvertedToBac());
        $this->assertFalse(AbstractOfCanvass::eligibleForBacConversion()->whereKey($abc->id)->exists());
        $this->assertFalse(BacResolutionResource::hasEligibleSource());
    }

    public function test_second_bac_from_same_abc_is_rejected(): void
    {
        $abc = $this->makeAbc();

        BacResolution::create([
            'abc_id' => $abc->id,
            'title' => 'First resolution',
            'date' => now(),
        ]);

        $this->expectException(ValidationException::class);

        BacResolution::create([
            'abc_id' => $abc->id,
            'title' => 'Duplicate resolution',
            'date' => now(),
        ]);
    }

    public function test_bac_without_abc_source_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        BacResolution::create([
            'title' => 'Missing source ABC',
            'date' => now(),
        ]);
    }

    public function test_create_page_redirects_when_no_eligible_abc(): void
    {
        $user = $this->makeAdmin();

        // No ABCs at all -> mount guard redirects back to the list.
        $this->actingAs($user)
            ->get('/admin/bac-resolutions/create')
            ->assertRedirect('/admin/bac-resolutions');
    }

    public function test_create_page_loads_when_eligible_abc_exists(): void
    {
        $this->makeAbc();

        $this->actingAs($this->makeAdmin())
            ->get('/admin/bac-resolutions/create')
            ->assertOk();
    }

    public function test_create_page_redirects_after_abc_converted(): void
    {
        $abc = $this->makeAbc();
        BacResolution::create([
            'abc_id' => $abc->id,
            'title' => 'Resolution title',
            'date' => now(),
        ]);

        // The only ABC is now converted -> no eligible sources remain.
        $this->actingAs($this->makeAdmin())
            ->get('/admin/bac-resolutions/create')
            ->assertRedirect('/admin/bac-resolutions');
    }

    public function test_bac_list_visible_for_permitted_user(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/bac-resolutions')
            ->assertOk();
    }

    public function test_bac_list_hides_create_button_and_shows_warning_when_no_eligible_abc(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/bac-resolutions')
            ->assertOk()
            ->assertDontSee('New BAC Resolution')
            // Wrapper class exists only in rendered markup (never in
            // Livewire's serialized snapshot).
            ->assertSee('fi-no-eligible-abc-warning')
            ->assertSee(BacResolution::NO_ELIGIBLE_ABC_MESSAGE);
    }

    public function test_bac_list_shows_create_button_when_eligible_abc_exists(): void
    {
        $this->makeAbc();

        $this->actingAs($this->makeAdmin())
            ->get('/admin/bac-resolutions')
            ->assertOk()
            ->assertSee('New BAC Resolution')
            ->assertDontSee('fi-no-eligible-abc-warning');
    }
}
