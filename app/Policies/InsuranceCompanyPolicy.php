<?php

namespace App\Policies;

use App\Models\InsuranceCompany;
use App\Models\User;

class InsuranceCompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function view(User $user, InsuranceCompany $insuranceCompany): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function update(User $user, InsuranceCompany $insuranceCompany): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function delete(User $user, InsuranceCompany $insuranceCompany): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }
}