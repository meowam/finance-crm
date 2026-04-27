<?php

namespace App\Policies;

use App\Models\ClaimNote;
use App\Models\User;

class ClaimNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function view(User $user, ClaimNote $claimNote): bool
    {
        if ($user->isAdmin() || $user->isSupervisor()) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        return (int) optional($claimNote->claim?->policy)->agent_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function update(User $user, ClaimNote $claimNote): bool
    {
        if ($user->isAdmin() || $user->isSupervisor()) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        return (int) optional($claimNote->claim?->policy)->agent_id === (int) $user->id;
    }

    public function delete(User $user, ClaimNote $claimNote): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }
}