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
        // Only the Administrator and Supply Officer roles may create suppliers.
        return $user->hasRole([
            User::ROLE_ADMIN,
            User::ROLE_SUPPLY_OFFICER,
        ]);
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('suppliers.update');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('suppliers.delete');
    }
}
