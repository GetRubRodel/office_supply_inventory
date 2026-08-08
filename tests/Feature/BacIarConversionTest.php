<?php

namespace Tests\Feature;

use App\Filament\Resources\BacResolutionResource;
use App\Models\BacResolution;
use App\Models\IarItem;
use App\Models\InspectionAcceptanceReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * End-to-end verification of the BAC Resolution �?" Inspection and Acceptance
 * Report (IAR) conversion workflow:
 *
 *   1. A BAC Resolution must be created from an eligible (unconverted) IAR.
 *   2. On creation, the BAC auto-hydrates a snapshot of the IAR (IAR no., PO
 *      no., supplier, inspection/acceptance details, per-item breakdown).
 *   3. The source IAR becomes ineligible for further conversions.
 *   4. Duplicate conversion is blocked at the model and database levels.
 */
class BacIarConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The User model does not implement FilamentUser, so Filament's
        // panel-auth middleware only permits access when APP_ENV=local.
        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modules = ['dashboard', 'users', 'supplies', 'suppliers', 'categories', 'purchase_requests', 'request_for_quotations', 'abstracts_of_canvass', 'purchase_orders', 'inspection_acceptance_reports', 'bac_resolutions', 'requisitions', 'rpcis', 'rsmi_reports', 'inventory_reports', 'audit_logs', 'system_settings', 'stock_in'];
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
            'admin' => ['bac_resolutions.view', 'bac_resolutions.create', 'bac_resolutions.update', 'bac_resolutions.delete'],
            'supply_officer' => ['bac_resolutions.view', 'bac_resolutions.create', 'bac_resolutions.update', 'bac_resolutions.delete'],
        ];
        foreach ($rolePermissions as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create([
            'username' => 'u'.uniqid(),
            'role' => 'admin',
            'status' => 'approved',
        ]);
        $user->assignRole('admin');

        return $user;
    }

    /**
     * Create an IAR with the given items and inspection/acceptance flags.
     */
    private function makeIar(array $itemSpecs = [
        ['stock_no' => 'SN-001', 'description' => 'Bond Paper', 'unit' => 'Ream', 'quantity' => 10, 'quantity_accepted' => 10, 'unit_cost' => 220.00],
        ['stock_no' => 'SN-002', 'description' => 'Ballpen', 'unit' => 'Piece', 'quantity' => 50, 'quantity_accepted' => 48, 'unit_cost' => 15.50],
    ], array $flags = []): InspectionAcceptanceReport
    {
        $iar = InspectionAcceptanceReport::create([
            'po_id' => null,
            'supplier_name' => 'Test Supplier Co.',
            'po_no' => 'PO-2026-0001',
            'date' => now(),
            'requisitioning_office_dept' => 'Administrative Division',
            'inspector_name' => 'ANA FE B. GALANTO',
            'inspector_designation' => 'Admin. Assistant II',
            'inspection_date' => now()->subDay(),
            'inspection_complete' => $flags['inspection_complete'] ?? true,
            'inspection_partial' => $flags['inspection_partial'] ?? false,
            'acceptor_name' => 'RHODELIA J. MANDOLADO',
            'acceptor_designation' => 'Admin. Officer IV',
            'acceptance_date' => now(),
            'acceptance_complete' => $flags['acceptance_complete'] ?? true,
            'acceptance_partial' => $flags['acceptance_partial'] ?? false,
            'status' => InspectionAcceptanceReport::STATUS_APPROVED,
        ]);

        foreach ($itemSpecs as $spec) {
            IarItem::create(['iar_id' => $iar->id] + $spec);
        }

        return $iar->fresh();
    }

    public function test_bac_creation_from_iar_hydrates_snapshot(): void
    {
        $iar = $this->makeIar();

        $bac = BacResolution::create([
            'iar_id' => $iar->id,
            'title' => 'AUTHORIZING THE CHR XII TO PROCURE',
            'date' => now(),
            'place' => 'Koronadal City, Philippines',
        ]);

        // Auto-generated resolution number in MM-SSS format.
        $this->assertMatchesRegularExpression('/^01-\d{3}$/', $bac->resolution_no);

        // Snapshot hydrated from the source IAR.
        $this->assertEquals($iar->iar_no, $bac->iar_no);
        $this->assertEquals('PO-2026-0001', $bac->po_no);
        $this->assertEquals('Test Supplier Co.', $bac->supplier_name);
        $this->assertEquals($iar->inspection_date->toDateString(), $bac->inspection_date->toDateString());
        $this->assertEquals($iar->acceptance_date->toDateString(), $bac->acceptance_date->toDateString());
        $this->assertEquals('COMPLETE INSPECTION; COMPLETE ACCEPTANCE', $bac->acceptance_details);

        // Item snapshot: 2 items, accepted quantities, computed totals.
        $this->assertCount(2, $bac->items_snapshot);
        $this->assertEquals('SN-001', $bac->items_snapshot[0]['stock_no']);
        $this->assertEquals('Bond Paper', $bac->items_snapshot[0]['description']);
        $this->assertEquals(10, $bac->items_snapshot[0]['quantity']);
        $this->assertEquals(220.00, (float) $bac->items_snapshot[0]['unit_cost']);
        $this->assertEquals(2200.00, (float) $bac->items_snapshot[0]['total']);

        // Total = (10 x 220) + (48 x 15.50) = 2200 + 744 = 2944.00
        $this->assertEquals(2944.00, $bac->getTotalAmount());
    }

    public function test_converted_iar_is_no_longer_eligible(): void
    {
        $iar = $this->makeIar();

        $this->assertFalse($iar->isConvertedToBac());
        $this->assertTrue(InspectionAcceptanceReport::eligibleForBacConversion()->whereKey($iar->id)->exists());
        $this->assertTrue(BacResolutionResource::hasEligibleIar());

        BacResolution::create([
            'iar_id' => $iar->id,
            'title' => 'Resolution title',
            'date' => now(),
        ]);

        $iar->refresh();
        $this->assertTrue($iar->isConvertedToBac());
        $this->assertFalse(InspectionAcceptanceReport::eligibleForBacConversion()->whereKey($iar->id)->exists());
        $this->assertFalse(BacResolutionResource::hasEligibleIar());
    }

    public function test_second_bac_from_same_iar_is_rejected(): void
    {
        $iar = $this->makeIar();

        BacResolution::create([
            'iar_id' => $iar->id,
            'title' => 'First resolution',
            'date' => now(),
        ]);

        $this->expectException(ValidationException::class);

        BacResolution::create([
            'iar_id' => $iar->id,
            'title' => 'Duplicate resolution',
            'date' => now(),
        ]);
    }

    public function test_bac_without_iar_source_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        BacResolution::create([
            'title' => 'Missing source IAR',
            'date' => now(),
        ]);
    }

    public function test_acceptance_summary_handles_partial_flags(): void
    {
        $iar = $this->makeIar([
            ['stock_no' => 'SN-001', 'description' => 'Bond Paper', 'unit' => 'Ream', 'quantity' => 10, 'quantity_accepted' => 5, 'unit_cost' => 100.00],
        ], [
            'inspection_complete' => false,
            'inspection_partial' => true,
            'acceptance_complete' => false,
            'acceptance_partial' => true,
        ]);

        $this->assertEquals('PARTIAL INSPECTION; PARTIAL ACCEPTANCE', $iar->acceptanceSummary());

        $bac = BacResolution::create([
            'iar_id' => $iar->id,
            'title' => 'Partial acceptance resolution',
            'date' => now(),
        ]);

        $this->assertEquals('PARTIAL INSPECTION; PARTIAL ACCEPTANCE', $bac->acceptance_details);
        $this->assertEquals(500.00, $bac->getTotalAmount());
    }

    public function test_create_page_redirects_when_no_eligible_iar(): void
    {
        $user = $this->makeAdmin();

        // No IARs at all -> mount guard redirects back to the list.
        $this->actingAs($user)
            ->get('/admin/bac-resolutions/create')
            ->assertRedirect('/admin/bac-resolutions');
    }

    public function test_create_page_loads_when_eligible_iar_exists(): void
    {
        $this->makeIar();

        $this->actingAs($this->makeAdmin())
            ->get('/admin/bac-resolutions/create')
            ->assertOk();
    }

    public function test_create_page_redirects_after_iar_converted(): void
    {
        $iar = $this->makeIar();
        BacResolution::create([
            'iar_id' => $iar->id,
            'title' => 'Resolution title',
            'date' => now(),
        ]);

        // The only IAR is now converted -> no eligible sources remain.
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
}
