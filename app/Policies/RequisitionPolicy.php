<?php

namespace App\Policies;

use App\Models\Requisition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RequisitionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('requisitions.view');
    }

    public function view(User $user, Requisition $requisition): bool
    {
        // Staff (own-records-only) may only view their own requisitions.
        if ($user->isOwnRecordsOnly() && $requisition->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('requisitions.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('requisitions.create');
    }

    public function update(User $user, Requisition $requisition): bool
    {
        return $user->hasPermissionTo('requisitions.update');
    }

    public function delete(User $user, Requisition $requisition): bool
    {
        return $user->hasPermissionTo('requisitions.delete');
    }

    public function approve(User $user, Requisition $requisition): bool
    {
        return $user->hasPermissionTo('requisitions.approve');
    }

    public function issue(User $user, Requisition $requisition): bool
    {
        return $user->hasPermissionTo('requisitions.issue');
    }

    public function receive(User $user, Requisition $requisition): bool
    {
        return $user->hasPermissionTo('requisitions.receive')
            && $requisition->user_id === $user->id;
    }
}
