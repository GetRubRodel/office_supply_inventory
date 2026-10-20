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
            'procurement_monitoring_reports',
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
            'procurement_monitoring_reports.view',
            'procurement_monitoring_reports.print',
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

            // Suppliers – Create/View/Edit (no delete)
            'suppliers.view', 'suppliers.create', 'suppliers.update',

            // Categories – Create/View/Edit (no delete)
            'categories.view', 'categories.create', 'categories.update',

            // Purchase Requests (PR) – Create, View, Print (edit/submit handled by workflow permissions)
            'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.update', 'purchase_requests.print',

            // Request for Quotation (RFQ) – Create/View/Edit/Print (no delete)
            'request_for_quotations.view', 'request_for_quotations.create', 'request_for_quotations.update', 'request_for_quotations.print',

            // Abstract of Bids and Canvass (ABC) – Create/View/Edit/Print (no delete)
            'abstracts_of_canvass.view', 'abstracts_of_canvass.create', 'abstracts_of_canvass.update', 'abstracts_of_canvass.print',

            // Purchase Order (PO) – Create/View/Edit/Print (no delete)
            'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.update', 'purchase_orders.print',

            // Inspection and Acceptance Report (IAR) – Full access, including Stock In
            'inspection_acceptance_reports.view', 'inspection_acceptance_reports.create', 'inspection_acceptance_reports.stock_in', 'inspection_acceptance_reports.print',

            // BAC Resolution – Create/View/Edit/Print (no delete)
            'bac_resolutions.view', 'bac_resolutions.create', 'bac_resolutions.update', 'bac_resolutions.print',

            // Requisitions (RIS) – Create, View, Edit, Issue, Receive, Print
            // (no approve/cancel/delete). Supply Officers may receive their own
            // RIS requests once items are issued; ownership is enforced by
            // User::canReceiveRequisition and RequisitionPolicy::receive.
            'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.issue', 'requisitions.receive', 'requisitions.print',

            // RPCI – Full access (View, Create, Edit, Delete, Print, Preview)
            'rpcis.view', 'rpcis.create', 'rpcis.update', 'rpcis.delete', 'rpcis.print', 'rpcis.preview',

            // RSMI Reports – Full access (View, Create, Edit, Delete, Print)
            'rsmi_reports.view', 'rsmi_reports.create', 'rsmi_reports.update', 'rsmi_reports.delete', 'rsmi_reports.print',

            // Inventory Reports – Full access (View, Create, Edit, Delete, Print)
            'inventory_reports.view', 'inventory_reports.create', 'inventory_reports.update', 'inventory_reports.delete', 'inventory_reports.print',

            // Procurement Monitoring Report – View + Print
            'procurement_monitoring_reports.view', 'procurement_monitoring_reports.print',

            // Stock In – Full access
            'stock_in.view', 'stock_in.create', 'stock_in.update', 'stock_in.delete',
        ]);

        // 3. Staff — Create and manage their OWN RIS requests; create Purchase
        //    Request drafts. Staff may create RIS, edit/print their own, and
        //    receive their own issued RIS. All Staff access is scoped to their
        //    own records by the RequisitionResource query and RequisitionPolicy
        //    (ownership checks). Staff may create PR drafts which the Supply
        //    Officer completes and submits for review. Staff
        //    may not approve, issue, delete, or cancel any RIS, and PR
        //    update/delete/approve remain with the existing roles.
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions([
            'dashboard.view',
            'supplies.view',
            // Purchase Requests (PR) – Create draft requests only; submission
            // for review (purchase_requests.update) stays with Supply
            // Officer / Admin.
            'purchase_requests.view', 'purchase_requests.create',
            'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.receive', 'requisitions.print',
            // View-only access to procurement pipeline modules so staff can
            // track the progress of their purchase requests through the
            // workflow. No create/update/delete permissions are granted.
            'request_for_quotations.view',
            'abstracts_of_canvass.view',
            'purchase_orders.view',
            'inspection_acceptance_reports.view',
            'bac_resolutions.view',
        ]);

        // 4. Property Custodian — full Create/View/Edit/Delete management over
        //    every Purchase Management and Inventory Management module (PR,
        //    RFQ, ABC, PO, IAR, BAC Resolution, Categories, Supplies,
        //    Suppliers, Inventory Report, RSMI, RPCI). Approval powers and the
        //    RIS (Requisition) workflow stay with their existing roles: on RIS
        //    the Property Custodian may view/create/print and edit their OWN
        //    submitted requests only (scoped by RequisitionPolicy) — no
        //    approve/issue/receive/delete.
        $propertyCustodian = Role::firstOrCreate(['name' => 'property_custodian', 'guard_name' => 'web']);
        $propertyCustodian->syncPermissions([
            'dashboard.view',

            // Purchase Management – Full CRUD + print
            'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.update', 'purchase_requests.delete', 'purchase_requests.print',
            'request_for_quotations.view', 'request_for_quotations.create', 'request_for_quotations.update', 'request_for_quotations.delete', 'request_for_quotations.print',
            'abstracts_of_canvass.view', 'abstracts_of_canvass.create', 'abstracts_of_canvass.update', 'abstracts_of_canvass.delete', 'abstracts_of_canvass.print',
            'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.update', 'purchase_orders.delete', 'purchase_orders.print',
            'inspection_acceptance_reports.view', 'inspection_acceptance_reports.create', 'inspection_acceptance_reports.update', 'inspection_acceptance_reports.delete', 'inspection_acceptance_reports.stock_in', 'inspection_acceptance_reports.print',
            'bac_resolutions.view', 'bac_resolutions.create', 'bac_resolutions.update', 'bac_resolutions.delete', 'bac_resolutions.print',

            // Inventory Management – Full CRUD (+ stock in for IAR)
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'supplies.view', 'supplies.create', 'supplies.update', 'supplies.delete',
            'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',

            // Report Management – Full CRUD + print
            'inventory_reports.view', 'inventory_reports.create', 'inventory_reports.update', 'inventory_reports.delete', 'inventory_reports.print',
            'rsmi_reports.view', 'rsmi_reports.create', 'rsmi_reports.update', 'rsmi_reports.delete', 'rsmi_reports.print',
            'rpcis.view', 'rpcis.create', 'rpcis.update', 'rpcis.delete', 'rpcis.print', 'rpcis.preview',

            // Requisitions (RIS) – View/Create/Print; update is scoped to own
            // requests by RequisitionPolicy. No approve/issue/receive/delete.
            'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.print',
        ]);

        // 5. Division Chief / Division Head — May create and submit their own
        //    RIS requests and, together with the Administrator, is the only
        //    role allowed to approve RIS requests (requisitions.approve).
        //    They may also receive (acknowledge) the RIS requests they
        //    themselves raised — the existing ownership scope keeps receipt
        //    strictly limited to the original requester. On Purchase Requests,
        //    may create draft PRs (view/create/print); submission for review,
        //    approval and editing remain with the existing roles (PC/SO/Admin
        //    complete and submit; Admin/Regional Director approve). Report
        //    Management modules are view-only for the Division Chief (no
        //    print/download/export); those remain with Admin and Supply Officer.
        $divisionChief = Role::firstOrCreate(['name' => 'division_chief', 'guard_name' => 'web']);
        $divisionChief->syncPermissions([
            'dashboard.view',
            'supplies.view',
            'suppliers.view',
            'categories.view',
            'purchase_requests.view', 'purchase_requests.create',
            'requisitions.view', 'requisitions.create', 'requisitions.approve', 'requisitions.update', 'requisitions.receive', 'requisitions.print',
            'request_for_quotations.view',
            'abstracts_of_canvass.view',
            'purchase_orders.view',
            'inspection_acceptance_reports.view',
            'bac_resolutions.view',
            'inventory_reports.view',
            'rpcis.view',
            'rsmi_reports.view',
            'procurement_monitoring_reports.view',
        ]);

        // 6. Regional Director — View/print only on Inventory Management and
        //    Report Management; retains approval authority on Purchase Requests.
        //    May create, edit (their own requests only) and submit RIS
        //    (Requisition and Issue Slip) requests which flow through the
        //    existing request → approval → issuance workflow with their
        //    existing roles. They may also receive (acknowledge) the RIS
        //    requests they themselves raised — the existing ownership scope
        //    (User::canReceiveRequisition / RequisitionPolicy::receive) keeps
        //    receipt strictly limited to the original requester.
        $regionalDirector = Role::firstOrCreate(['name' => 'regional_director', 'guard_name' => 'web']);
        $regionalDirector->syncPermissions([
            'dashboard.view',

            // Inventory Management – View only (no create/update/delete/stock in/stock out)
            'supplies.view',
            'suppliers.view',
            'categories.view',
            'stock_in.view',

            // Report Management – View only (Print/Download/Export restricted
            // to Admin and Supply Officer)
            'inventory_reports.view',
            'rsmi_reports.view',
            'procurement_monitoring_reports.view',
            'rpcis.view',

            // Purchase Management – View + Print + Create (approve on Purchase Requests only)
            'purchase_requests.view', 'purchase_requests.create', 'purchase_requests.approve', 'purchase_requests.print',
            'request_for_quotations.view', 'request_for_quotations.print',
            'abstracts_of_canvass.view', 'abstracts_of_canvass.print',
            'purchase_orders.view', 'purchase_orders.print',
            'inspection_acceptance_reports.view', 'inspection_acceptance_reports.print',
            'bac_resolutions.view', 'bac_resolutions.print',
            'requisitions.view', 'requisitions.create', 'requisitions.update', 'requisitions.receive', 'requisitions.print',
        ]);

        // ─── Assign roles to existing users based on their role_column ───
        $roleMap = [
            'admin'               => 'admin',
            'supply_officer'      => 'supply_officer',
            'staff'               => 'staff',
            'division_chief'      => 'division_chief',
            'regional_director'   => 'regional_director',
            'property_custodian'  => 'property_custodian',
        ];

        User::all()->each(function (User $user) use ($roleMap) {
            $roleName = $roleMap[$user->role] ?? null;
            if ($roleName) {
                $user->syncRoles([$roleName]);
            }
        });
    }
}
