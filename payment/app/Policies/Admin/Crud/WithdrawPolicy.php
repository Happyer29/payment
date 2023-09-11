<?php

namespace App\Policies\Admin\Crud;

use App\Http\Resources\Bank\Withdraw;
use Illuminate\Auth\Access\Response;
use App\Models\User;

class WithdrawPolicy
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
    public function view(User $user, Withdraw $withdraw): bool
    {
        return $user->isAnyManager() or $withdraw->getShop()->getOwnerId() === $user->getId();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Withdraw $withdraw): bool
    {
        return $user->isAnyManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Withdraw $withdraw): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Withdraw $withdraw): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Withdraw $withdraw): bool
    {
        return false;
    }

    public function manage(User $user, Withdraw $withdraw): bool
    {
        return $user->isAnyManager();
    }

    public function manageAny(User $user): bool
    {
        return $user->isAnyManager();
    }
}
