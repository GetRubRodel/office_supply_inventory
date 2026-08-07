<?php

namespace App\Policies;

use App\Models\Supply;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('supplies.view');
    }

    public function view(User $user, Supply $supply): bool
    {
        return $user->hasPermissionTo('supplies.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('supplies.create');
    }

    public function update(User $user, Supply $supply): bool
    {
        return $user->hasPermissionTo('supplies.update');
    }

    public function delete(User $user, Supply $supply): bool
    {
        return $user->hasPermissionTo('supplies.delete');
    }
}
