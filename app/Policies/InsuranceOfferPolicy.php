<?php

namespace App\Policies;

use App\Models\InsuranceOffer;
use App\Models\User;

class InsuranceOfferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function view(User $user, InsuranceOffer $insuranceOffer): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function update(User $user, InsuranceOffer $insuranceOffer): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function delete(User $user, InsuranceOffer $insuranceOffer): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }
}