<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Module Names ───────────────────────────────────────────
        $modules = [
            'dashboard',
            'users',
            'supplies',
            'suppliers',
            'categories',
            'purchase_requests',
            'request_for_quotations',
            'abstracts_of_canvass',
            'purchase_orders',
            'inspection_acceptance_reports',
            'bac_resolutions',
            'requisitions',
            'rpcis',
            'rsmi_reports',
            'inventory_reports',
            'audit_logs',
            'system_settings',
            'stock_in',
        ];

        // ─── Create Permissions ─────────────────────────────────────
        $actions = ['view', 'create', 'update', 'delete', 'approve'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$module}.{$action}", 'guard_name' => 'web']);
            }
        }

        // Extra domain-specific permissions
        $extras = [
            'requisitions.issue',
            'requisitions.receive',
            'purchase_requests.approve',
            'purchase_requests.print',
            'requisitions.print',
            'request_for_quotations.print',
            'abstracts_of_canvass.print',
            'purchase_orders.print',
            'inspection_acceptance_reports.print',
            'bac_resolutions.print',
            'rpcis.print',
            'rpcis.preview',
            'inspection_acceptance_reports.stock_in',
            'inventory_reports.view',
            'inventory_reports.print',
            'rsmi_reports.view',
            'rsmi_reports.print',
        ];

        foreach ($extras as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ─── Create Roles ──────────────────────────────────────────

        // 1. Administrator — Full Access
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // 2. Supply Officer
        $supplyOfficer = Role::firstOrCreate(['name' => 'supply_officer', 'guard_name' => 'web']);
        $supplyOfficer->syncPermissions([
            // Dashboard – Full access
            'dashboard.view',

            // Supplies – Full access
            'supplies.view', 'supplies.create', 'supplies.update', 'supplies.delete',

            // Suppliers – Full access
            'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',

            // Categories – Full access
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',

            // Purchase Requests (PR) – Create, View, Print (edit/submit handled by workflow permissions)
            'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.update', 'purchase_requests.print',

            // Request for Quotation (RFQ) – Full access
            'request_for_quotations.view', 'request_for_quotations.create', 'request_for_quotations.update', 'request_for_quotations.delete', 'request_for_quotations.print',

            // Abstract of Bids and Canvass (ABC) – Full access
            'abstracts_of_canvass.view', 'abstracts_of_canvass.create', 'abstracts_of_canvass.update', 'abstracts_of_canvass.delete', 'abstracts_of_canvass.print',

            // Purchase Order (PO) – Full access
            'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.update', 'purchase_orders.delete', 'purchase_orders.print',

            // Inspection and Acceptance Report (IAR) – Full access, including Stock In
            'inspection_acceptance_reports.view', 'inspection_acceptance_reports.create', 'inspection_acceptance_reports.stock_in', 'inspection_acceptance_reports.print',

            // BAC Resolution – Full access
            'bac_resolutions.view', 'bac_resolutions.create', 'bac_resolutions.update', 'bac_resolutions.delete', 'bac_resolutions.print',

            // Requisitions (RIS) – Create, View, Edit, Issue, Print (no approve/cancel/receive/delete)
            'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.issue', 'requisitions.print',

            // RPCI – Full access (View, Create, Edit, Delete, Print, Preview)
            'rpcis.view', 'rpcis.create', 'rpcis.update', 'rpcis.delete', 'rpcis.print', 'rpcis.preview',

            // RSMI Reports – Full access (View, Create, Edit, Delete, Print)
            'rsmi_reports.view', 'rsmi_reports.create', 'rsmi_reports.update', 'rsmi_reports.delete', 'rsmi_reports.print',

            // Inventory Reports – Full access (View, Create, Edit, Delete, Print)
            'inventory_reports.view', 'inventory_reports.create', 'inventory_reports.update', 'inventory_reports.delete', 'inventory_reports.print',

            // Stock In – Full access
            'stock_in.view', 'stock_in.create', 'stock_in.update', 'stock_in.delete',
        ]);

        // 3. Property Custodian
        $propertyCustodian = Role::firstOrCreate(['name' => 'property_custodian', 'guard_name' => 'web']);
        $propertyCustodian->syncPermissions([
            // Dashboard – Full access
            'dashboard.view',

            // Inventory Reports – Full access (View, Create, Edit, Delete, Print)
            'inventory_reports.view', 'inventory_reports.create', 'inventory_reports.update', 'inventory_reports.delete', 'inventory_reports.print',

            // RSMI Reports – Full access (View, Create, Edit, Delete, Print)
            'rsmi_reports.view', 'rsmi_reports.create', 'rsmi_reports.update', 'rsmi_reports.delete', 'rsmi_reports.print',

            // RPCI – Full access (View, Create, Edit, Delete, Print, Preview)
            'rpcis.view', 'rpcis.create', 'rpcis.update', 'rpcis.delete', 'rpcis.print', 'rpcis.preview',

            // Requisitions (RIS) – View + Print only
            'requisitions.view', 'requisitions.print',

            // Categories – View only
            'categories.view',

            // Supplies – View only
            'supplies.view',

            // Suppliers – View only
            'suppliers.view',

            // Purchase Requests (PR) – View + Print only
            'purchase_requests.view', 'purchase_requests.print',

            // Request for Quotation (RFQ) – View + Print only
            'request_for_quotations.view', 'request_for_quotations.print',

            // Abstract of Bids and Canvass (ABC) – View + Print only
            'abstracts_of_canvass.view', 'abstracts_of_canvass.print',

            // BAC Resolution – View + Print only
            'bac_resolutions.view', 'bac_resolutions.print',

            // Purchase Order (PO) – View + Print only
            'purchase_orders.view', 'purchase_orders.print',

            // Inspection and Acceptance Report (IAR) – View + Print only
            'inspection_acceptance_reports.view', 'inspection_acceptance_reports.print',
        ]);

        // 4. Staff
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions([
            'dashboard.view',
            'supplies.view',
            'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.print',
            'requisitions.view', 'requisitions.create', 'requisitions.receive', 'requisitions.print',
        ]);

        // 5. Division Chief / Division Head
        $divisionChief = Role::firstOrCreate(['name' => 'division_chief', 'guard_name' => 'web']);
        $divisionChief->syncPermissions([
            'dashboard.view',
            'supplies.view',
            'suppliers.view',
            'categories.view',
            'purchase_requests.view', 'purchase_requests.print',
            'requisitions.view', 'requisitions.approve', 'requisitions.update', 'requisitions.print',
            'request_for_quotations.view', 'request_for_quotations.print',
            'abstracts_of_canvass.view', 'abstracts_of_canvass.print',
            'purchase_orders.view', 'purchase_orders.print',
            'inspection_acceptance_reports.view', 'inspection_acceptance_reports.print',
            'bac_resolutions.view', 'bac_resolutions.print',
            'inventory_reports.view', 'inventory_reports.print',
            'rpcis.view', 'rpcis.print',
            'rsmi_reports.view', 'rsmi_reports.print',
        ]);

        // 6. Regional Director — View-only with approval authority on Purchase Requests
        $regionalDirector = Role::firstOrCreate(['name' => 'regional_director', 'guard_name' => 'web']);
        $regionalDirector->syncPermissions([
            'dashboard.view',
            'suppliers.view',
            'purchase_requests.view', 'purchase_requests.approve', 'purchase_requests.print',
            'request_for_quotations.view', 'request_for_quotations.print',
            'abstracts_of_canvass.view', 'abstracts_of_canvass.print',
            'purchase_orders.view', 'purchase_orders.print',
            'inspection_acceptance_reports.view', 'inspection_acceptance_reports.print',
            'bac_resolutions.view', 'bac_resolutions.print',
            'requisitions.view', 'requisitions.print',
            'inventory_reports.view', 'inventory_reports.print',
        ]);

        // ─── Assign roles to existing users based on their role_column ───
        $roleMap = [
            'admin'               => 'admin',
            'supply_officer'      => 'supply_officer',
            'property_custodian'  => 'property_custodian',
            'staff'               => 'staff',
            'division_chief'      => 'division_chief',
            'regional_director'   => 'regional_director',
        ];

        User::all()->each(function (User $user) use ($roleMap) {
            $roleName = $roleMap[$user->role] ?? null;
            if ($roleName) {
                $user->syncRoles([$roleName]);
            }
        });
    }
}
