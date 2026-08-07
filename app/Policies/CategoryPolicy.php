<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('categories.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('categories.update');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('categories.delete');
    }
}
