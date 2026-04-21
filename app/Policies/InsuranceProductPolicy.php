<?php

namespace App\Policies;

use App\Models\InsuranceProduct;
use App\Models\User;

class InsuranceProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function view(User $user, InsuranceProduct $insuranceProduct): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function update(User $user, InsuranceProduct $insuranceProduct): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function delete(User $user, InsuranceProduct $insuranceProduct): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }
}