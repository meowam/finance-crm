<?php

namespace App\Policies;

use App\Models\InsuranceCategory;
use App\Models\User;

class InsuranceCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function view(User $user, InsuranceCategory $insuranceCategory): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function update(User $user, InsuranceCategory $insuranceCategory): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function delete(User $user, InsuranceCategory $insuranceCategory): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }
}