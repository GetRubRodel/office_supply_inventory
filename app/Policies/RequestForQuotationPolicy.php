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
        return $user->hasPermissionTo('request_for_quotations.update');
    }

    public function delete(User $user, RequestForQuotation $requestForQuotation): bool
    {
        return $user->hasPermissionTo('request_for_quotations.delete');
    }
}
