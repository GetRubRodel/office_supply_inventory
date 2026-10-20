<?php

namespace App\Policies;

use App\Models\AbstractOfCanvass;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AbstractOfCanvassPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('abstracts_of_canvass.view');
    }

    public function view(User $user, AbstractOfCanvass $abstractOfCanvass): bool
    {
        return $user->hasPermissionTo('abstracts_of_canvass.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('abstracts_of_canvass.create');
    }

    public function update(User $user, AbstractOfCanvass $abstractOfCanvass): bool
    {
        // Division Chief may not update ABC records.
        if ($user->isDivisionChief()) {
            return false;
        }

        return $user->hasPermissionTo('abstracts_of_canvass.update');
    }

    public function delete(User $user, AbstractOfCanvass $abstractOfCanvass): bool
    {
        // Supply Officer may not delete ABC records.
        if ($user->isSupplyOfficer()) {
            return false;
        }

        return $user->hasPermissionTo('abstracts_of_canvass.delete');
    }
}
