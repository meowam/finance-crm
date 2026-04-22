<?php

namespace App\Policies;

use App\Models\PolicyPayment;
use App\Models\User;

class PolicyPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function view(User $user, PolicyPayment $policyPayment): bool
    {
        return $policyPayment->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function update(User $user, PolicyPayment $policyPayment): bool
    {
        return $policyPayment->isVisibleTo($user);
    }

    public function delete(User $user, PolicyPayment $policyPayment): bool
    {
        return ($user->isAdmin() || $user->isSupervisor()) && $policyPayment->isVisibleTo($user);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }
}