<?php

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('purchase_requests.view');
    }

    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        // Staff (own-records-only) may only view their own purchase requests.
        if ($user->isOwnRecordsOnly() && $purchaseRequest->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('purchase_requests.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('purchase_requests.create');
    }

    public function update(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->hasPermissionTo('purchase_requests.update');
    }

    public function delete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->hasPermissionTo('purchase_requests.delete');
    }

    public function approve(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->hasPermissionTo('purchase_requests.approve');
    }
}
