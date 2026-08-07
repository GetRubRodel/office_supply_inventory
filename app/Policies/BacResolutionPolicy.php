<?php

namespace App\Policies;

use App\Models\BacResolution;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BacResolutionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('bac_resolutions.view');
    }

    public function view(User $user, BacResolution $bacResolution): bool
    {
        return $user->hasPermissionTo('bac_resolutions.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('bac_resolutions.create');
    }

    public function update(User $user, BacResolution $bacResolution): bool
    {
        return $user->hasPermissionTo('bac_resolutions.update');
    }

    public function delete(User $user, BacResolution $bacResolution): bool
    {
        return $user->hasPermissionTo('bac_resolutions.delete');
    }
}
