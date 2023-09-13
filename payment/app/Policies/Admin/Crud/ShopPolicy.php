<?php

namespace App\Policies\Admin\Crud;

use App\Http\Resources\Bank\Shop;
use Illuminate\Auth\Access\Response;
use App\Models\User;

class ShopPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAnyManager();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Shop $shop): bool
    {
        return $user->isAnyManager() || $user->id === $shop->getOwnerId();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Shop $shop): bool
    {
        return $user->isAdmin() || $user->id === $shop->getOwnerId();
    }

    public function moderate(User $user, Shop $shop): bool
    {
        return $user->isAnyManager();
    }

    public function moderateAny(User $user): bool
    {
        return $user->isAnyManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Shop $shop): bool
    {
        return $user->isAdmin() || $user->id === $shop->getOwnerId();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Shop $shop): bool
    {
        return $user->isAdmin() || $user->id === $shop->getOwnerId();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Shop $shop): bool
    {
        return $user->isAdmin() || $user->id === $shop->getOwnerId();
    }
}