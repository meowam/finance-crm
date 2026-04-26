<?php

namespace App\Services\Assignments;

use App\Models\User;

class ManagerAssignmentService
{
    public function resolveLeastBusyManager(): ?User
    {
        return User::query()
            ->where('role', 'manager')
            ->where('is_active', true)
            ->withCount([
                'assignedLeadRequests as open_leads_count' => function ($query) {
                    $query->whereIn('status', ['new', 'in_progress']);
                },
                'assignedClients as active_clients_count' => function ($query) {
                    $query->where('status', '!=', 'archived');
                },
                'policies as active_policies_count' => function ($query) {
                    $query->where('status', 'active');
                },
            ])
            ->orderBy('open_leads_count')
            ->orderBy('active_clients_count')
            ->orderBy('active_policies_count')
            ->orderBy('id')
            ->first();
    }

    public function resolveLeastBusyManagerId(): ?int
    {
        return $this->resolveLeastBusyManager()?->id;
    }
}