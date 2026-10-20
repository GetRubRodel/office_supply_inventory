<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('purchase_orders.view');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermissionTo('purchase_orders.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('purchase_orders.create');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // Division Chief may not update PO records.
        if ($user->isDivisionChief()) {
            return false;
        }

        return $user->hasPermissionTo('purchase_orders.update');
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // Supply Officer may not delete PO records.
        if ($user->isSupplyOfficer()) {
            return false;
        }

        return $user->hasPermissionTo('purchase_orders.delete');
    }
}
