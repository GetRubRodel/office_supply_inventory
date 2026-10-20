<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RsmiReportDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mirror RolePermissionSeeder's full permission set so sidebar
        // navigation never hits Spatie's PermissionDoesNotExist.
        $modules = ['dashboard', 'users', 'supplies', 'suppliers', 'categories', 'purchase_requests', 'request_for_quotations', 'abstracts_of_canvass', 'purchase_orders', 'inspection_acceptance_reports', 'bac_resolutions', 'requisitions', 'rpcis', 'rsmi_reports', 'inventory_reports', 'procurement_monitoring_reports', 'audit_logs', 'system_settings', 'stock_in'];
        foreach ($modules as $module) {
            foreach (['view', 'create', 'update', 'delete', 'approve'] as $action) {
                Permission::findOrCreate("{$module}.{$action}", 'web');
            }
        }
        foreach (['requisitions.issue', 'requisitions.receive', 'purchase_requests.print', 'requisitions.print', 'request_for_quotations.print', 'abstracts_of_canvass.print', 'purchase_orders.print', 'inspection_acceptance_reports.print', 'bac_resolutions.print', 'rpcis.print', 'rpcis.preview', 'inspection_acceptance_reports.stock_in', 'inventory_reports.print', 'rsmi_reports.print'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $rolePermissions = [
            'admin' => Permission::all()->pluck('name')->all(),
            'property_custodian' => ['dashboard.view', 'supplies.view', 'requisitions.view', 'rsmi_reports.view', 'rsmi_reports.print'],
            'division_chief' => ['dashboard.view', 'supplies.view', 'requisitions.view', 'rsmi_reports.view'],
            'regional_director' => ['dashboard.view', 'supplies.view', 'requisitions.view', 'requisitions.create', 'requisitions.update', 'rsmi_reports.view'],
            // Staff must NOT see/download the RSMI module.
            'staff' => ['dashboard.view', 'supplies.view', 'purchase_requests.view', 'purchase_requests.create', 'requisitions.view'],
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

    private function makeSupply(float $unitCost = 250.00): Supply
    {
        $category = Category::create([
            'name' => 'Test Category '.uniqid(),
            'description' => 'Test category',
            'status' => 'active',
        ]);

        return Supply::create([
            'stock_no' => 'STK-'.uniqid(),
            'name' => 'Bond Paper A4 '.uniqid(),
            'category_id' => $category->id,
            'unit' => 'ream',
            'unit_cost' => $unitCost,
            'current_stock' => 100,
            'status' => 'active',
        ]);
    }

    /**
     * Create a requisition (RIS) with issued items in the given status.
     */
    private function makeRequisition(User $user, string $status, array $items = []): Requisition
    {
        $requisition = Requisition::create([
            'user_id' => $user->id,
            'status' => $status,
            'issued_by_date' => '2026-08-10',
        ]);

        foreach ($items as $item) {
            RequisitionItem::create([
                'requisition_id' => $requisition->id,
                'supply_id' => $item['supply_id'],
                'quantity_requested' => $item['quantity'],
                'quantity_issued' => $item['quantity'],
                'issued_unit_cost' => $item['unit_cost'] ?? null,
                'price' => $item['price'] ?? null,
            ]);
        }

        return $requisition;
    }

    // ─── Download ──────────────────────────────────────────────────

    public function test_rsmi_download_returns_docx(): void
    {
        $admin = $this->makeUser('admin');
        $supply = $this->makeSupply();
        $this->makeRequisition($admin, Requisition::STATUS_ISSUED, [
            ['supply_id' => $supply->id, 'quantity' => 5, 'unit_cost' => 250.00],
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\RsmiReport::class)
            ->call('downloadReport')
            ->assertFileDownloaded('RSMI-Report-' . now()->format('Y-m-d') . '.docx');
    }

    public function test_staff_cannot_access_the_rsmi_module(): void
    {
        $this->makeSupply();
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)
            ->get('/admin/rsmi-report')
            ->assertForbidden();
    }

    public function test_download_aborts_for_users_without_permission(): void
    {
        $staff = $this->makeUser('staff');
        $this->actingAs($staff);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(\App\Filament\Pages\RsmiReport::class)->downloadReport();
    }

    public function test_rsmi_download_uses_issued_data_only(): void
    {
        $admin = $this->makeUser('admin');
        $supply = $this->makeSupply();
        $pendingSupply = $this->makeSupply(100.00);

        // Issued transactions appear in the report and download.
        $this->makeRequisition($admin, Requisition::STATUS_ISSUED, [
            ['supply_id' => $supply->id, 'quantity' => 3, 'unit_cost' => 250.00],
        ]);
        $this->makeRequisition($admin, Requisition::STATUS_RECEIVED, [
            ['supply_id' => $supply->id, 'quantity' => 2, 'unit_cost' => 250.00],
        ]);

        // A not-yet-issued requisition must NOT appear in the items table.
        $pendingReq = $this->makeRequisition($admin, Requisition::STATUS_REQUESTED, [
            ['supply_id' => $pendingSupply->id, 'quantity' => 9, 'unit_cost' => 100.00],
        ]);

        $this->actingAs($admin)
            ->get('/admin/rsmi-report')
            ->assertOk()
            ->assertSee($supply->name, false)
            ->assertSee('RIS-' . now()->year . '-0001', false)
            ->assertDontSee($pendingReq->ris_no, false);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\RsmiReport::class)
            ->call('downloadReport')
            ->assertFileDownloaded('RSMI-Report-' . now()->format('Y-m-d') . '.docx');
    }

    public function test_rsmi_download_does_not_modify_records(): void
    {
        $admin = $this->makeUser('admin');
        $supply = $this->makeSupply();
        $requisition = $this->makeRequisition($admin, Requisition::STATUS_ISSUED, [
            ['supply_id' => $supply->id, 'quantity' => 5, 'unit_cost' => 250.00],
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\RsmiReport::class)
            ->call('downloadReport')
            ->assertFileDownloaded('RSMI-Report-' . now()->format('Y-m-d') . '.docx');

        $this->assertSame(1, Requisition::count());
        $this->assertSame(1, RequisitionItem::count());
        $this->assertDatabaseHas('requisitions', [
            'id' => $requisition->id,
            'status' => Requisition::STATUS_ISSUED,
        ]);
        $this->assertDatabaseHas('supplies', [
            'id' => $supply->id,
            'current_stock' => 100,
        ]);
    }

    public function test_existing_print_route_is_unchanged(): void
    {
        $admin = $this->makeUser('admin');
        $supply = $this->makeSupply();
        $this->makeRequisition($admin, Requisition::STATUS_ISSUED, [
            ['supply_id' => $supply->id, 'quantity' => 2, 'unit_cost' => 250.00],
        ]);

        $this->actingAs($admin)
            ->get(route('ris.rsmi'))
            ->assertOk()
            ->assertSee('REPORT OF SUPPLIES AND MATERIALS ISSUED', false)
            ->assertSee($supply->name, false);
    }

    public function test_print_and_download_are_denied_for_division_chief_and_regional_director(): void
    {
        $this->makeSupply();

        foreach (['division_chief', 'regional_director'] as $role) {
            $user = $this->makeUser($role);

            // View access is kept; the print permission is revoked.
            $this->assertTrue($user->hasPermissionTo('rsmi_reports.view'));
            $this->assertFalse($user->hasPermissionTo('rsmi_reports.print'));

            // The module page stays viewable but the actions are hidden.
            $this->actingAs($user)
                ->get('/admin/rsmi-report')
                ->assertOk()
                ->assertDontSee('Print RSMI', false)
                ->assertDontSee('Download RSMI', false);

            // Direct print route and download endpoint are forbidden.
            $this->actingAs($user)
                ->get(route('ris.rsmi'))
                ->assertForbidden();

            try {
                app(\App\Filament\Pages\RsmiReport::class)->downloadReport();
                $this->fail("{$role} download should abort with 403.");
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
    }
}
