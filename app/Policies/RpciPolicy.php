<?php

namespace App\Policies;

use App\Models\Rpci;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RpciPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('rpcis.view');
    }

    public function view(User $user, Rpci $rpci): bool
    {
        return $user->hasPermissionTo('rpcis.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('rpcis.create');
    }

    public function update(User $user, Rpci $rpci): bool
    {
        return $user->hasPermissionTo('rpcis.update');
    }

    public function delete(User $user, Rpci $rpci): bool
    {
        return $user->hasPermissionTo('rpcis.delete');
    }
}
