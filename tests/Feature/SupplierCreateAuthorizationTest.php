<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplierCreateAuthorizationTest extends TestCase
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
        // Spatie's PermissionDoesNotExist. Role assignments below mirror the live
        // production role_has_permissions table, not the seeder.
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

        // Mirror the production role -> permission assignments.
        $rolePermissions = [
            'admin' => ['suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete'],
            'supply_officer' => ['suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete'],
            'property_custodian' => ['suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete'],
            'division_chief' => ['suppliers.view'],
            'staff' => [],
            'regional_director' => ['suppliers.view'],
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

    public function test_policy_allows_create_only_for_admin_supply_officer_and_property_custodian(): void
    {
        foreach (['admin', 'supply_officer', 'property_custodian'] as $role) {
            $this->assertTrue($this->makeUser($role)->can('create', Supplier::class), "$role should be allowed");
        }

        foreach (['division_chief', 'staff', 'regional_director'] as $role) {
            $this->assertFalse($this->makeUser($role)->can('create', Supplier::class), "$role should be denied");
        }
    }

    public function test_create_button_is_visible_for_roles_with_create_permission(): void
    {
        // Roles granted suppliers.create (Administrator, Supply Officer,
        // Property Custodian) see the create button on the list page.
        foreach (['admin', 'supply_officer', 'property_custodian'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get('/admin/suppliers')
                ->assertOk()
                ->assertSee('/admin/suppliers/create', false);
        }
    }

    public function test_create_button_is_hidden_for_read_only_roles(): void
    {
        // Roles that can open the Supplier list (have suppliers.view) but must not create.
        foreach (['division_chief', 'regional_director'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get('/admin/suppliers')
                ->assertOk()
                ->assertDontSee('/admin/suppliers/create', false);
        }
    }

    public function test_module_is_inaccessible_for_staff(): void
    {
        // No suppliers.view at all -> the whole module is hidden, so no create button.
        $this->actingAs($this->makeUser('staff'))
            ->get('/admin/suppliers')
            ->assertForbidden();
    }

    public function test_regional_director_can_view_suppliers_without_create(): void
    {
        // Regional Director has suppliers.view (viewer-only) -> the module is
        // visible and the list loads, but the create button is not shown.
        $this->actingAs($this->makeUser('regional_director'))
            ->get('/admin/suppliers')
            ->assertOk()
            ->assertDontSee('/admin/suppliers/create', false);
    }

    public function test_direct_url_is_allowed_for_create_roles_and_denied_for_others(): void
    {
        // Roles with suppliers.create can open the create page; every other
        // role is blocked at the resource level (canCreate()).
        foreach (['admin', 'supply_officer', 'property_custodian'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get('/admin/suppliers/create')
                ->assertOk();
        }

        foreach (['division_chief', 'staff', 'regional_director'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get('/admin/suppliers/create')
                ->assertForbidden();
        }
    }
}
