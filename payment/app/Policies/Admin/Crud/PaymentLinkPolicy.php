<?php

namespace App\Policies\Admin\Crud;

use App\Http\Resources\Bank\PaymentLink;
use Illuminate\Auth\Access\Response;
use App\Models\User;

class PaymentLinkPolicy
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
    public function view(User $user, PaymentLink $link): bool
    {
        if(!$user->id){
            return false;
        }
        return $user->isAnyManager() || $user->id === $link->getShop()?->getOwnerId();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function moderate(User $user, PaymentLink $link): bool
    {
        return $user->isAnyManager();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function moderateAny(User $user): bool
    {
        return $user->isAnyManager();
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
    public function update(User $user, PaymentLink $link): bool
    {
        return $user->isAnyManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PaymentLink $link): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PaymentLink $link): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PaymentLink $link): bool
    {
        return false;
    }
}
