<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\LeadRequest;
use App\Models\Policy;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AdminProblemRecordsTable extends Widget
{
    protected string $view = 'filament.widgets.admin-problem-records-table';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && $user->isAdmin();
    }

    protected function getViewData(): array
    {
        $leadProblems = LeadRequest::query()
            ->with('assignedUser')
            ->where(function (Builder $query) {
                $query
                    ->whereNull('assigned_user_id')
                    ->orWhereDoesntHave('assignedUser', function (Builder $managerQuery) {
                        $managerQuery
                            ->where('role', 'manager')
                            ->where('is_active', true);
                    });
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (LeadRequest $lead) => [
                'type' => 'Вхідна заявка',
                'label' => $lead->display_label,
                'problem' => 'Немає активного менеджера',
                'url' => "/admin/lead-requests/{$lead->id}/edit",
            ]);

        $clientProblems = Client::query()
            ->with('assignedUser')
            ->where(function (Builder $query) {
                $query
                    ->whereNull('assigned_user_id')
                    ->orWhereDoesntHave('assignedUser', function (Builder $managerQuery) {
                        $managerQuery
                            ->where('role', 'manager')
                            ->where('is_active', true);
                    });
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Client $client) => [
                'type' => 'Клієнт',
                'label' => $client->display_label,
                'problem' => 'Немає активного менеджера',
                'url' => "/admin/clients/{$client->id}/edit",
            ]);

        $policyProblems = Policy::query()
            ->with('agent')
            ->where(function (Builder $query) {
                $query
                    ->whereNull('agent_id')
                    ->orWhereDoesntHave('agent', function (Builder $managerQuery) {
                        $managerQuery
                            ->where('role', 'manager')
                            ->where('is_active', true);
                    });
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Policy $policy) => [
                'type' => 'Поліс',
                'label' => $policy->policy_number ?: ('Поліс #' . $policy->id),
                'problem' => 'Немає активного менеджера',
                'url' => "/admin/policies/{$policy->id}/edit",
            ]);

        $records = $leadProblems
            ->merge($clientProblems)
            ->merge($policyProblems)
            ->values();

        return [
            'records' => $records,
            'total' => $records->count(),
        ];
    }
}