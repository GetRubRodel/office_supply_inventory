<?php

namespace App\Policies;

use App\Models\StockInRecord;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockInRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('stock_in.view');
    }

    public function view(User $user, StockInRecord $stockInRecord): bool
    {
        return $user->hasPermissionTo('stock_in.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('stock_in.create');
    }

    public function update(User $user, StockInRecord $stockInRecord): bool
    {
        return $user->hasPermissionTo('stock_in.update');
    }

    public function delete(User $user, StockInRecord $stockInRecord): bool
    {
        return $user->hasPermissionTo('stock_in.delete');
    }
}
