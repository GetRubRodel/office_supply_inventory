<?php

namespace App\Policies;

use App\Models\RequestForQuotation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RequestForQuotationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('request_for_quotations.view');
    }

    public function view(User $user, RequestForQuotation $requestForQuotation): bool
    {
        return $user->hasPermissionTo('request_for_quotations.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('request_for_quotations.create');
    }

    public function update(User $user, RequestForQuotation $requestForQuotation): bool
    {
        // Division Chief may not update RFQ records.
        if ($user->isDivisionChief()) {
            return false;
        }

        return $user->hasPermissionTo('request_for_quotations.update');
    }

    public function delete(User $user, RequestForQuotation $requestForQuotation): bool
    {
        // Supply Officer may not delete RFQ records.
        if ($user->isSupplyOfficer()) {
            return false;
        }

        return $user->hasPermissionTo('request_for_quotations.delete');
    }
}
