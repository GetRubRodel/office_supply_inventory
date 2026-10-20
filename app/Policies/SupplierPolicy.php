<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;


class SupplierPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('suppliers.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('suppliers.view');
    }

    public function create(User $user): bool
    {
        // Roles granted the suppliers.create permission (Administrator,
        // Supply Officer, Property Custodian) may create suppliers.
        return $user->hasPermissionTo('suppliers.create');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        // Division Chief may not update Supplier records.
        if ($user->isDivisionChief()) {
            return false;
        }

        return $user->hasPermissionTo('suppliers.update');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        // Supply Officer may not delete Supplier records.
        if ($user->isSupplyOfficer()) {
            return false;
        }

        return $user->hasPermissionTo('suppliers.delete');
    }
}
