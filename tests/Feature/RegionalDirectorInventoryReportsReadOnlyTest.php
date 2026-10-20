<?php

namespace Tests\Feature;

use App\Filament\Resources\RequisitionResource;
use App\Models\Category;
use App\Models\Requisition;
use App\Models\Rpci;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegionalDirectorInventoryReportsReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The User model does not implement FilamentUser, so Filament's panel-auth
        // middleware permits access only when APP_ENV=local (as in production).
        // Mirror that here so panel access is governed by the app's own checks.
        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mirror RolePermissionSeeder's full permission set so sidebar navigation
        // (which mounts every page and checks its *.view permission) never hits
        // Spatie's PermissionDoesNotExist.
        $modules = ['dashboard', 'users', 'supplies', 'suppliers', 'categories', 'purchase_requests', 'request_for_quotations', 'abstracts_of_canvass', 'purchase_orders', 'inspection_acceptance_reports', 'bac_resolutions', 'requisitions', 'rpcis', 'rsmi_reports', 'inventory_reports', 'procurement_monitoring_reports', 'audit_logs', 'system_settings', 'stock_in'];
        foreach ($modules as $module) {
            foreach (['view', 'create', 'update', 'delete', 'approve'] as $action) {
                Permission::findOrCreate("{$module}.{$action}", 'web');
            }
        }
        $extras = ['requisitions.issue', 'requisitions.receive', 'purchase_requests.print', 'requisitions.print', 'request_for_quotations.print', 'abstracts_of_canvass.print', 'purchase_orders.print', 'inspection_acceptance_reports.print', 'bac_resolutions.print', 'rpcis.print', 'rpcis.preview', 'inspection_acceptance_reports.stock_in', 'inventory_reports.print', 'rsmi_reports.print', 'procurement_monitoring_reports.print'];
        foreach ($extras as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        // Mirror the production role -> permission assignments.
        $rolePermissions = [
            'admin' => Permission::all()->pluck('name')->all(),
            'supply_officer' => [
                'dashboard.view',
                'supplies.view', 'supplies.create', 'supplies.update', 'supplies.delete',
                'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',
                'categories.view', 'categories.create', 'categories.update', 'categories.delete',
                'stock_in.view', 'stock_in.create', 'stock_in.update', 'stock_in.delete',
                'requisitions.view', 'requisitions.issue',
            ],
            'property_custodian' => [
                'dashboard.view',
                'supplies.view', 'suppliers.view', 'categories.view',
                'rpcis.view', 'rpcis.print', 'rpcis.preview',
                'rsmi_reports.view', 'rsmi_reports.print',
                'inventory_reports.view', 'inventory_reports.print',
                'requisitions.view', 'requisitions.print',
            ],
            'division_chief' => [
                'dashboard.view',
                'supplies.view', 'suppliers.view', 'categories.view',
                'rpcis.view',
                'rsmi_reports.view',
                'inventory_reports.view',
                'requisitions.view', 'requisitions.print',
            ],
            'staff' => [
                'dashboard.view', 'supplies.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.print',
                'requisitions.view', 'requisitions.print',
            ],
            'regional_director' => [
                'dashboard.view',
                'supplies.view', 'suppliers.view', 'categories.view', 'stock_in.view',
                'inventory_reports.view',
                'rsmi_reports.view',
                'rpcis.view',
                'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.approve', 'purchase_requests.print',
                'request_for_quotations.view', 'request_for_quotations.print',
                'abstracts_of_canvass.view', 'abstracts_of_canvass.print',
                'purchase_orders.view', 'purchase_orders.print',
                'inspection_acceptance_reports.view', 'inspection_acceptance_reports.print',
                'bac_resolutions.view', 'bac_resolutions.print',
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

    private function makeCategory(): Category
    {
        return Category::create([
            'name' => 'Test Category '.uniqid(),
            'description' => 'Test category',
            'status' => 'active',
        ]);
    }

    private function makeSupplier(): Supplier
    {
        return Supplier::create([
            'name' => 'Test Supplier '.uniqid(),
            'phone' => '09170000000',
        ]);
    }

    private function makeSupply(Category $category): Supply
    {
        return Supply::create([
            'stock_no' => 'STK-'.uniqid(),
            'name' => 'Test Item '.uniqid(),
            'category_id' => $category->id,
            'unit' => 'pcs',
            'reorder_level' => 5,
            'current_stock' => 10,
            'status' => 'active',
        ]);
    }

    private function makeRpci(User $user): Rpci
    {
        return Rpci::create([
            'report_no' => 'RPCI-TEST-'.uniqid(),
            'report_date' => now()->toDateString(),
            'inventory_type' => 'office_supplies',
            'accountable_person' => 'Test Officer',
            'accountable_position' => 'Property Custodian',
            'status' => 'draft',
            'user_id' => $user->id,
            'created_by' => $user->name,
        ]);
    }

    // ─── Inventory Management – view access ────────────────────────

    public function test_regional_director_can_view_all_inventory_modules(): void
    {
        $rd = $this->makeUser('regional_director');

        $this->actingAs($rd)->get('/admin/supplies')->assertOk();
        $this->actingAs($rd)->get('/admin/categories')->assertOk();
        $this->actingAs($rd)->get('/admin/suppliers')->assertOk();
    }

    public function test_regional_director_can_open_inventory_detail_pages(): void
    {
        $rd = $this->makeUser('regional_director');
        $category = $this->makeCategory();
        $supply = $this->makeSupply($category);
        $supplier = $this->makeSupplier();

        // Supplies has a dedicated detail page.
        $this->actingAs($rd)->get("/admin/supplies/{$supply->id}")->assertOk();

        // Categories/Suppliers expose list-only navigation (no detail page),
        // so the list responses must carry the read-only experience.
        $this->actingAs($rd)->get('/admin/categories')->assertOk();
        $this->actingAs($rd)->get('/admin/suppliers')->assertOk();

        // The records are visible in the lists.
        $this->actingAs($rd)
            ->get('/admin/categories')
            ->assertOk()
            ->assertSee($category->name, false);
        $this->actingAs($rd)
            ->get('/admin/suppliers')
            ->assertOk()
            ->assertSee($supplier->name, false);
    }

    // ─── Report Management – view access ───────────────────────────

    public function test_regional_director_can_view_all_report_modules(): void
    {
        $rd = $this->makeUser('regional_director');

        $this->actingAs($rd)->get('/admin/rpcis')->assertOk();
        $this->actingAs($rd)->get('/admin/inventory-report')->assertOk();
        $this->actingAs($rd)->get('/admin/rsmi-report')->assertOk();
    }

    public function test_regional_director_can_open_rpci_detail_but_cannot_print_or_export(): void
    {
        $rd = $this->makeUser('regional_director');
        $rpci = $this->makeRpci($rd);

        // The detail page remains available (rpcis.view).
        $this->actingAs($rd)->get("/admin/rpcis/{$rpci->id}")->assertOk();

        // Print and Preview/PDF-export are revoked for the Regional Director.
        $this->actingAs($rd)->get("/rpci/print/{$rpci->id}")->assertForbidden();
        $this->actingAs($rd)->get("/rpci/preview/{$rpci->id}")->assertForbidden();
    }

    public function test_regional_director_report_print_and_download_are_denied(): void
    {
        $rd = $this->makeUser('regional_director');

        // Permission layer: view stays, print/preview/export are revoked on
        // every Report Management module.
        $this->assertTrue($rd->hasPermissionTo('inventory_reports.view'));
        $this->assertTrue($rd->hasPermissionTo('rsmi_reports.view'));
        $this->assertTrue($rd->hasPermissionTo('rpcis.view'));
        $this->assertFalse($rd->hasPermissionTo('inventory_reports.print'));
        $this->assertFalse($rd->hasPermissionTo('rsmi_reports.print'));
        $this->assertFalse($rd->hasPermissionTo('rpcis.print'));
        $this->assertFalse($rd->hasPermissionTo('rpcis.preview'));

        // The report modules remain viewable, and the Print/Download header
        // actions are hidden from the UI.
        $this->actingAs($rd)
            ->get('/admin/inventory-report')
            ->assertOk()
            ->assertDontSee('Print Report', false)
            ->assertDontSee('Download Report', false);
        $this->actingAs($rd)
            ->get('/admin/rsmi-report')
            ->assertOk()
            ->assertDontSee('Print RSMI', false)
            ->assertDontSee('Download RSMI', false);

        // Direct print/download URLs are forbidden.
        $this->actingAs($rd)->get('/ris/rsmi')->assertForbidden();
        $this->actingAs($rd)->get(route('procurement_monitoring.print'))->assertForbidden();

        // Download endpoints abort with 403 when invoked directly.
        foreach ([
            \App\Filament\Pages\InventoryReport::class,
            \App\Filament\Pages\RsmiReport::class,
            \App\Filament\Pages\ProcurementMonitoringReport::class,
        ] as $page) {
            try {
                app($page)->downloadReport();
                $this->fail("{$page} download should abort for the Regional Director.");
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
    }

    // ─── Policies – view only ──────────────────────────────────────

    public function test_regional_director_policies_allow_view_but_deny_write(): void
    {
        $rd = $this->makeUser('regional_director');
        $category = $this->makeCategory();
        $supply = $this->makeSupply($category);
        $supplier = $this->makeSupplier();
        $rpci = $this->makeRpci($rd);

        // View is allowed on every Inventory/Report module.
        $this->assertTrue($rd->can('viewAny', Supply::class));
        $this->assertTrue($rd->can('view', $supply));
        $this->assertTrue($rd->can('viewAny', Category::class));
        $this->assertTrue($rd->can('view', $category));
        $this->assertTrue($rd->can('viewAny', Supplier::class));
        $this->assertTrue($rd->can('view', $supplier));
        $this->assertTrue($rd->can('viewAny', Rpci::class));
        $this->assertTrue($rd->can('view', $rpci));

        // Write is denied on every Inventory/Report module.
        foreach ([Supply::class, Category::class, Supplier::class, Rpci::class] as $model) {
            $this->assertFalse($rd->can('create', $model), "$model create should be denied");
        }
        $this->assertFalse($rd->can('update', $supply));
        $this->assertFalse($rd->can('delete', $supply));
        $this->assertFalse($rd->can('update', $category));
        $this->assertFalse($rd->can('delete', $category));
        $this->assertFalse($rd->can('update', $supplier));
        $this->assertFalse($rd->can('delete', $supplier));
        $this->assertFalse($rd->can('update', $rpci));
        $this->assertFalse($rd->can('delete', $rpci));

        // Role helpers deny every inventory write path.
        $this->assertFalse($rd->canManageInventory());
        $this->assertFalse($rd->canIssueItems());
        $this->assertFalse($rd->canAccessMasterData());
    }

    // ─── Direct URLs – write operations forbidden ──────────────────

    public function test_regional_director_write_urls_are_forbidden(): void
    {
        $rd = $this->makeUser('regional_director');
        $category = $this->makeCategory();
        $supply = $this->makeSupply($category);
        $rpci = $this->makeRpci($rd);

        $this->actingAs($rd)
            ->get('/admin/supplies/create')->assertForbidden();
        $this->actingAs($rd)
            ->get("/admin/supplies/{$supply->id}/edit")->assertForbidden();
        $this->actingAs($rd)
            ->get('/admin/categories/create')->assertForbidden();
        $this->actingAs($rd)
            ->get("/admin/categories/{$category->id}/edit")->assertForbidden();
        $this->actingAs($rd)
            ->get('/admin/suppliers/create')->assertForbidden();
        $this->actingAs($rd)
            ->get('/admin/rpcis/create')->assertForbidden();
        $this->actingAs($rd)
            ->get("/admin/rpcis/{$rpci->id}/edit")->assertForbidden();
    }

    public function test_regional_director_create_buttons_are_hidden(): void
    {
        $rd = $this->makeUser('regional_director');

        $this->actingAs($rd)
            ->get('/admin/supplies')
            ->assertOk()
            ->assertDontSee('/admin/supplies/create', false);

        $this->actingAs($rd)
            ->get('/admin/categories')
            ->assertOk()
            ->assertDontSee('/admin/categories/create', false);

        $this->actingAs($rd)
            ->get('/admin/rpcis')
            ->assertOk()
            ->assertDontSee('/admin/rpcis/create', false);
    }

    // ─── Stock In / Stock Out (Issue) denied ───────────────────────

    public function test_regional_director_cannot_stock_in_or_issue(): void
    {
        $rd = $this->makeUser('regional_director');

        $this->assertFalse($rd->hasPermissionTo('inspection_acceptance_reports.stock_in'));
        $this->assertFalse($rd->hasPermissionTo('requisitions.issue'));
        $this->assertFalse($rd->hasPermissionTo('stock_in.create'));
        $this->assertFalse($rd->hasPermissionTo('stock_in.update'));
        $this->assertFalse($rd->hasPermissionTo('stock_in.delete'));
    }

    // ─── RIS creation (Regional Director) ────────────────────────────
    //
    // Regional Directors may create and submit their own RIS requests
    // (requisitions.create granted by the seeder). This is the single
    // write-path exception to their otherwise read-only access; approval,
    // issuance, deletion and receipt stay with the existing roles, and
    // update is scoped to their own requests only.

    public function test_regional_director_can_create_and_submit_ris(): void
    {
        $rd = $this->makeUser('regional_director');

        // Permission layer: RD holds requisitions.create.
        $this->assertTrue($rd->hasPermissionTo('requisitions.create'));
        $this->assertTrue($rd->can('create', Requisition::class));

        // Filament resource gate + Create page.
        $this->actingAs($rd);
        $this->assertTrue(RequisitionResource::canCreate());

        $this->actingAs($rd)
            ->get('/admin/requisitions/create')
            ->assertOk();

        // The Create RIS button is visible on the list page.
        $this->actingAs($rd)
            ->get('/admin/requisitions')
            ->assertOk()
            ->assertSee('/admin/requisitions/create', false);

        // A submitted RIS is owned by the RD and enters the existing
        // workflow as a "requested" requisition awaiting approval.
        $ris = Requisition::create([
            'user_id' => $rd->id,
            'division' => 'Administrative',
            'office' => 'Administrative Division',
            'entity_name' => 'CHR Region XII',
            'purpose' => 'Office supplies request',
            'status' => Requisition::STATUS_REQUESTED,
        ]);
        $this->assertSame(Requisition::STATUS_REQUESTED, $ris->status);
        $this->assertSame($rd->id, $ris->user_id);
    }

    public function test_regional_director_ris_workflow_boundaries_are_preserved(): void
    {
        $rd = $this->makeUser('regional_director');
        $other = $this->makeUser('staff');
        $own = Requisition::create([
            'user_id' => $rd->id,
            'division' => 'Administrative',
            'office' => 'Administrative Division',
            'entity_name' => 'CHR Region XII',
            'purpose' => 'Office supplies request',
            'status' => Requisition::STATUS_REQUESTED,
        ]);
        $others = Requisition::create([
            'user_id' => $other->id,
            'division' => 'Administrative',
            'office' => 'Administrative Division',
            'entity_name' => 'CHR Region XII',
            'purpose' => 'Office supplies request',
            'status' => Requisition::STATUS_REQUESTED,
        ]);

        // RD gains create, update and receive, but each is scoped to their
        // own requests: approval, issuance and deletion remain with their
        // existing roles.
        $this->assertTrue($rd->hasPermissionTo('requisitions.update'));
        $this->assertTrue($rd->hasPermissionTo('requisitions.receive'));
        $this->assertFalse($rd->hasPermissionTo('requisitions.approve'));
        $this->assertFalse($rd->hasPermissionTo('requisitions.issue'));
        $this->assertFalse($rd->hasPermissionTo('requisitions.delete'));

        $this->assertTrue($rd->can('update', $own));
        $this->assertFalse($rd->can('update', $others));
        $this->assertFalse($rd->can('approve', $own));
        $this->assertFalse($rd->can('issue', $own));
        $this->assertFalse($rd->can('delete', $own));
        $this->assertTrue($rd->can('receive', $own));
        $this->assertFalse($rd->can('receive', $others));
    }

    // ─── Other roles unchanged ─────────────────────────────────────

    public function test_other_roles_permissions_unchanged(): void
    {
        // Admin retains full write abilities.
        $admin = $this->makeUser('admin');
        $this->assertTrue($admin->can('create', Supply::class));
        $this->assertTrue($admin->can('create', Category::class));
        $this->assertTrue($admin->can('create', Supplier::class));
        $this->assertTrue($admin->can('create', Rpci::class));

        // Supply Officer retains write abilities.
        $so = $this->makeUser('supply_officer');
        $this->assertTrue($so->can('create', Supply::class));
        $this->assertTrue($so->can('update', $this->makeSupply($this->makeCategory())));

        // Property Custodian and Division Chief remain view-only on the
        // Inventory modules (print/download on Report Management is
        // restricted to Admin, Supply Officer and Property Custodian).
        foreach (['property_custodian', 'division_chief'] as $role) {
            $user = $this->makeUser($role);
            $this->assertTrue($user->can('viewAny', Supply::class));
            $this->assertFalse($user->can('create', Supply::class));
            $this->assertFalse($user->can('create', Category::class));
        }

        // Staff remains locked out of the Inventory/Report modules entirely.
        $staff = $this->makeUser('staff');
        $this->assertFalse($staff->can('create', Supply::class));
        $this->assertFalse($staff->can('viewAny', Category::class));
        $this->assertFalse($staff->can('viewAny', Rpci::class));
    }
}
