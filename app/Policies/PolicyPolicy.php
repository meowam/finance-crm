<?php

namespace App\Policies;

use App\Models\Policy;
use App\Models\User;

class PolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function view(User $user, Policy $policy): bool
    {
        return $policy->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function update(User $user, Policy $policy): bool
    {
        return $policy->isVisibleTo($user);
    }

    public function delete(User $user, Policy $policy): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }
}