<?php

namespace App\Services\Reports;

use App\Models\Claim;
use App\Models\Client;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ManagerReportsService
{
    public function getManagersForFilter(?User $authUser): Collection
    {
        $query = User::query()
            ->where('role', 'manager')
            ->where('is_active', true)
            ->orderBy('name');

        if ($authUser instanceof User && $authUser->isManager()) {
            $query->whereKey($authUser->id);
        }

        return $query->get(['id', 'name']);
    }

    public function resolveManagerId(array $filters, ?User $authUser): ?int
    {
        if ($authUser instanceof User && $authUser->isManager()) {
            return (int) $authUser->id;
        }

        return filled($filters['manager_id'] ?? null)
            ? (int) $filters['manager_id']
            : null;
    }

    public function getSummaryRows(array $filters, ?User $authUser): array
    {
        $managers = $this->getManagersForFilter($authUser);

        $managerId = $this->resolveManagerId($filters, $authUser);

        if ($managerId) {
            $managers = $managers->where('id', $managerId)->values();
        }

        return $managers
            ->map(function (User $manager) use ($filters) {
                return $this->buildManagerRow($manager, $filters);
            })
            ->values()
            ->all();
    }

    public function getDetailData(array $filters, ?User $authUser): ?array
    {
        $managerId = $this->resolveManagerId($filters, $authUser);

        if (! $managerId) {
            return null;
        }

        $manager = User::query()
            ->where('role', 'manager')
            ->find($managerId);

        if (! $manager) {
            return null;
        }

        return $this->buildManagerRow($manager, $filters);
    }

    public function getSummaryTotals(array $rows): array
    {
        $totals = [
            'new_clients' => 0,
            'lead_clients' => 0,
            'active_clients' => 0,
            'policies_total' => 0,
            'policies_active' => 0,
            'premium_sum' => 0.0,
            'payments_paid' => 0,
            'payments_scheduled' => 0,
            'payments_overdue' => 0,
            'claims_total' => 0,
            'claims_amount_claimed' => 0.0,
            'claims_amount_paid' => 0.0,
        ];

        foreach ($rows as $row) {
            $totals['new_clients'] += (int) ($row['new_clients'] ?? 0);
            $totals['lead_clients'] += (int) ($row['lead_clients'] ?? 0);
            $totals['active_clients'] += (int) ($row['active_clients'] ?? 0);
            $totals['policies_total'] += (int) ($row['policies_total'] ?? 0);
            $totals['policies_active'] += (int) ($row['policies_active'] ?? 0);
            $totals['premium_sum'] += (float) ($row['premium_sum'] ?? 0);
            $totals['payments_paid'] += (int) ($row['payments_paid'] ?? 0);
            $totals['payments_scheduled'] += (int) ($row['payments_scheduled'] ?? 0);
            $totals['payments_overdue'] += (int) ($row['payments_overdue'] ?? 0);
            $totals['claims_total'] += (int) ($row['claims_total'] ?? 0);
            $totals['claims_amount_claimed'] += (float) ($row['claims_amount_claimed'] ?? 0);
            $totals['claims_amount_paid'] += (float) ($row['claims_amount_paid'] ?? 0);
        }

        return $totals;
    }

    protected function buildManagerRow(User $manager, array $filters): array
    {
        $clientsQuery = $this->clientsQuery($manager, $filters);
        $policiesQuery = $this->policiesQuery($manager, $filters);
        $paymentsQuery = $this->paymentsQuery($manager, $filters);
        $claimsQuery = $this->claimsQuery($manager, $filters);

        return [
            'manager_id' => $manager->id,
            'manager_name' => $manager->name,

            'new_clients' => (clone $clientsQuery)->count(),
            'lead_clients' => (clone $clientsQuery)->where('status', 'lead')->count(),
            'active_clients' => (clone $clientsQuery)->where('status', 'active')->count(),

            'policies_total' => (clone $policiesQuery)->count(),
            'policies_active' => (clone $policiesQuery)->where('status', 'active')->count(),
            'premium_sum' => (float) ((clone $policiesQuery)->sum('premium_amount') ?: 0),

            'payments_paid' => (clone $paymentsQuery)->where('status', 'paid')->count(),
            'payments_scheduled' => (clone $paymentsQuery)->where('status', 'scheduled')->count(),
            'payments_overdue' => (clone $paymentsQuery)->where('status', 'overdue')->count(),

            'claims_total' => (clone $claimsQuery)->count(),
            'claims_amount_claimed' => (float) ((clone $claimsQuery)->sum('amount_claimed') ?: 0),
            'claims_amount_paid' => (float) ((clone $claimsQuery)->sum('amount_paid') ?: 0),
        ];
    }

    protected function clientsQuery(User $manager, array $filters): Builder
    {
        [$from, $until] = $this->resolveDates($filters);

        return Client::query()
            ->where('assigned_user_id', $manager->id)
            ->when(
                $filters['client_source'] ?? null,
                fn (Builder $query, $source) => $query->where('source', $source)
            )
            ->whereBetween('created_at', [$from, $until]);
    }

    protected function policiesQuery(User $manager, array $filters): Builder
    {
        [$from, $until] = $this->resolveDates($filters);

        return Policy::query()
            ->where('agent_id', $manager->id)
            ->when(
                $filters['policy_status'] ?? null,
                fn (Builder $query, $status) => $query->where('status', $status)
            )
            ->when(
                $filters['client_source'] ?? null,
                fn (Builder $query, $source) => $query->whereHas(
                    'client',
                    fn (Builder $clientQuery) => $clientQuery->where('source', $source)
                )
            )
            ->whereBetween('created_at', [$from, $until]);
    }

    protected function paymentsQuery(User $manager, array $filters): Builder
    {
        [$from, $until] = $this->resolveDates($filters);

        return PolicyPayment::query()
            ->whereHas('policy', function (Builder $policyQuery) use ($manager, $filters) {
                $policyQuery->where('agent_id', $manager->id);

                if (filled($filters['policy_status'] ?? null)) {
                    $policyQuery->where('status', $filters['policy_status']);
                }

                if (filled($filters['client_source'] ?? null)) {
                    $policyQuery->whereHas(
                        'client',
                        fn (Builder $clientQuery) => $clientQuery->where('source', $filters['client_source'])
                    );
                }
            })
            ->whereBetween('created_at', [$from, $until]);
    }

    protected function claimsQuery(User $manager, array $filters): Builder
    {
        [$from, $until] = $this->resolveDates($filters);

        return Claim::query()
            ->whereHas('policy', function (Builder $policyQuery) use ($manager, $filters) {
                $policyQuery->where('agent_id', $manager->id);

                if (filled($filters['policy_status'] ?? null)) {
                    $policyQuery->where('status', $filters['policy_status']);
                }

                if (filled($filters['client_source'] ?? null)) {
                    $policyQuery->whereHas(
                        'client',
                        fn (Builder $clientQuery) => $clientQuery->where('source', $filters['client_source'])
                    );
                }
            })
            ->whereBetween('reported_at', [$from, $until]);
    }

    protected function resolveDates(array $filters): array
    {
        $from = filled($filters['date_from'] ?? null)
            ? Carbon::parse($filters['date_from'])->startOfDay()
            : now()->subDays(30)->startOfDay();

        $until = filled($filters['date_until'] ?? null)
            ? Carbon::parse($filters['date_until'])->endOfDay()
            : now()->endOfDay();

        if ($from->gt($until)) {
            [$from, $until] = [$until->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $until];
    }
}