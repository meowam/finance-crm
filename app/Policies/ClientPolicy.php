<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function view(User $user, Client $client): bool
    {
        return $client->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || $user->isManager();
    }

    public function update(User $user, Client $client): bool
    {
        return $client->isVisibleTo($user);
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }
}