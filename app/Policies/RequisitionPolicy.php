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
        if (! $user->hasPermissionTo('requisitions.view')) {
            return false;
        }

        // Staff may only view the RIS requests they submitted themselves.
        if ($user->isStaff()) {
            return $requisition->user_id === $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('requisitions.create');
    }

    public function update(User $user, Requisition $requisition): bool
    {
        if (! $user->hasPermissionTo('requisitions.update')) {
            return false;
        }

        // Staff, Property Custodians and Regional Directors may only edit the
        // RIS requests they submitted themselves (and only while they are
        // still in the "requested" state, enforced by the table/header
        // actions). Admin, Supply Officer and Division Chief keep their
        // existing broader edit scope.
        if ($user->isStaff() || $user->isPropertyCustodian() || $user->isRegionalDirector()) {
            return $requisition->user_id === $user->id;
        }

        return true;
    }

    public function delete(User $user, Requisition $requisition): bool
    {
        return $user->hasPermissionTo('requisitions.delete');
    }

    public function approve(User $user, Requisition $requisition): bool
    {
        // RIS approval is reserved for the Administrator and Division Chief
        // roles only. A Division Chief cannot approve their own request; the
        // Administrator can approve any request. The permission grant
        // (requisitions.approve) and the role check must agree;
        // Requisition::transitionTo() enforces the same rule on the backend,
        // so no one can approve via a direct URL or raw request.
        return $user->hasPermissionTo('requisitions.approve')
            && $user->canApproveRequisition($requisition);
    }

    public function issue(User $user, Requisition $requisition): bool
    {
        return $user->canIssueRequisition($requisition);
    }

    public function receive(User $user, Requisition $requisition): bool
    {
        return $user->hasPermissionTo('requisitions.receive')
            && $requisition->user_id === $user->id;
    }
}
