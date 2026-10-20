<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Grant `requisitions.receive` to the Supply Officer role.
 *
 * Supply Officers may raise their own RIS requests (issued by the
 * Administrator). They must be able to acknowledge receipt of those items,
 * which requires the `requisitions.receive` permission. The permission was
 * previously withheld from the role, so the Receive action was hidden even
 * for a Supply Officer's own issued RIS. Ownership is still enforced by
 * User::canReceiveRequisition() and RequisitionPolicy::receive().
 *
 * The RolePermissionSeeder has been updated as well; this migration brings
 * existing databases in line without requiring a full re-seed.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'requisitions.receive',
            'guard_name' => 'web',
        ]);

        $role = Role::where('name', 'supply_officer')
            ->where('guard_name', 'web')
            ->first();

        if ($role && ! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::where('name', 'supply_officer')
            ->where('guard_name', 'web')
            ->first();

        if ($role && $role->hasPermissionTo('requisitions.receive')) {
            $role->revokePermissionTo('requisitions.receive');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
