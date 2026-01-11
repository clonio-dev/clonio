<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TransferRun;
use App\Models\User;

class TransferRunPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TransferRun $transferRun): bool
    {
        return $user->id === $transferRun->user_id;
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
    public function update(User $user, TransferRun $transferRun): bool
    {
        return $user->id === $transferRun->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TransferRun $transferRun): bool
    {
        return $user->id === $transferRun->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TransferRun $transferRun): bool
    {
        return $user->id === $transferRun->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TransferRun $transferRun): bool
    {
        return $user->id === $transferRun->user_id;
    }
}
