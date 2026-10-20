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
        // Any role holding purchase_requests.view may open any purchase
        // request (Staff track the full procurement pipeline read-only).
        return $user->hasPermissionTo('purchase_requests.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('purchase_requests.create');
    }

    public function update(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if (! $user->hasPermissionTo('purchase_requests.update')) {
            return false;
        }

        // Staff may only update their own purchase requests.
        if ($user->isOwnRecordsOnly()) {
            return $purchaseRequest->user_id === $user->id;
        }

        return true;
    }

    public function delete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if (! $user->hasPermissionTo('purchase_requests.delete')) {
            return false;
        }

        // Staff may only delete their own purchase requests.
        if ($user->isOwnRecordsOnly()) {
            return $purchaseRequest->user_id === $user->id;
        }

        return true;
    }

    public function approve(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->hasPermissionTo('purchase_requests.approve');
    }

    /**
     * Submit a Purchase Request for review — only the original requester
     * (the user who created the PR) and the Administrator may submit.
     */
    public function submit(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->canSubmitPurchaseRequest($purchaseRequest);
    }
}
