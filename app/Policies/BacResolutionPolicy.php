<?php

namespace App\Policies;

use App\Filament\Resources\BacResolutionResource;
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
        // A BAC Resolution can only be created when at least one Abstract of
        // Bids and Canvass (ABC) exists that has not yet been converted into
        // a BAC Resolution. Enforced here (backend authorization) as well as
        // in the Filament resource and the model guard.
        return $user->hasPermissionTo('bac_resolutions.create')
            && BacResolutionResource::hasEligibleSource();
    }

    public function update(User $user, BacResolution $bacResolution): bool
    {
        // Division Chief may not update BAC Resolution records.
        if ($user->isDivisionChief()) {
            return false;
        }

        return $user->hasPermissionTo('bac_resolutions.update');
    }

    public function delete(User $user, BacResolution $bacResolution): bool
    {
        // Supply Officer may not delete BAC Resolution records.
        if ($user->isSupplyOfficer()) {
            return false;
        }

        return $user->hasPermissionTo('bac_resolutions.delete');
    }
}
