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

        // Mirror the production role -> permission assignments.
        $rolePermissions = [
            'admin' => ['suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete'],
            'supply_officer' => ['suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete'],
            'property_custodian' => ['suppliers.view'],
            'division_chief' => ['suppliers.view'],
            'staff' => [],
            'regional_director' => [],
        ];

        foreach ($rolePermissions as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create([
            'username' => 'u'.uniqid(),
            'role' => $role,
            'status' => 'approved',
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_policy_allows_create_only_for_admin_and_supply_officer(): void
    {
        foreach (['admin', 'supply_officer'] as $role) {
            $this->assertTrue($this->makeUser($role)->can('create', Supplier::class), "$role should be allowed");
        }

        foreach (['property_custodian', 'division_chief', 'staff', 'regional_director'] as $role) {
            $this->assertFalse($this->makeUser($role)->can('create', Supplier::class), "$role should be denied");
        }
    }

    public function test_create_button_is_visible_for_admin_and_supply_officer(): void
    {
        foreach (['admin', 'supply_officer'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get('/admin/suppliers')
                ->assertOk()
                ->assertSee('/admin/suppliers/create', false);
        }
    }

    public function test_create_button_is_hidden_for_read_only_roles(): void
    {
        // Roles that can open the Supplier list (have suppliers.view) but must not create.
        foreach (['property_custodian', 'division_chief'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get('/admin/suppliers')
                ->assertOk()
                ->assertDontSee('/admin/suppliers/create', false);
        }
    }

    public function test_module_is_inaccessible_for_staff_and_regional_director(): void
    {
        // No suppliers.view at all -> the whole module is hidden, so no create button.
        foreach (['staff', 'regional_director'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get('/admin/suppliers')
                ->assertForbidden();
        }
    }

    public function test_direct_url_is_denied_for_all_non_creator_roles(): void
    {
        foreach (['property_custodian', 'division_chief', 'staff', 'regional_director'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get('/admin/suppliers/create')
                ->assertForbidden();
        }
    }

    public function test_direct_url_is_allowed_for_admin_and_supply_officer(): void
    {
        foreach (['admin', 'supply_officer'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get('/admin/suppliers/create')
                ->assertOk();
        }
    }
}
