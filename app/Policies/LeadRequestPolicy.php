<?php

namespace App\Policies;

use App\Models\LeadRequest;
use App\Models\User;

class LeadRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function view(User $user, LeadRequest $leadRequest): bool
    {
        return $leadRequest->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function update(User $user, LeadRequest $leadRequest): bool
    {
        return $leadRequest->isVisibleTo($user);
    }

    public function delete(User $user, LeadRequest $leadRequest): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }
}