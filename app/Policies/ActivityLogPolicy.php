<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSupervisor()) {
            return $activityLog->actor_role === 'manager' || (int) $activityLog->actor_id === (int) $user->id;
        }

        if ($user->isManager()) {
            return (int) $activityLog->actor_id === (int) $user->id;
        }

        return false;
    }
}