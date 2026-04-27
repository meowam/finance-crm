<?php

namespace App\Filament\Widgets;

use App\Models\Claim;
use App\Models\LeadRequest;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ManagerLoadHighlights extends BaseWidget
{
    protected ?string $heading = 'Ключові навантаження менеджерів';

    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            '2xl' => 4,
        ];
    }

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && $user->isSupervisor();
    }

    protected function getStats(): array
    {
        $managers = User::query()
            ->where('role', 'manager')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $rows = $managers->map(function (User $manager): array {
            $activeLeads = LeadRequest::query()
                ->where('assigned_user_id', $manager->id)
                ->whereIn('status', ['new', 'in_progress'])
                ->count();

            $activePolicies = Policy::query()
                ->where('status', 'active')
                ->whereHas('client', fn (Builder $query) => $query->where('assigned_user_id', $manager->id))
                ->count();

            $overduePayments = PolicyPayment::query()
                ->where('status', 'overdue')
                ->whereHas('policy.client', fn (Builder $query) => $query->where('assigned_user_id', $manager->id))
                ->count();

            $claims = Claim::query()
                ->whereHas('policy.client', fn (Builder $query) => $query->where('assigned_user_id', $manager->id))
                ->count();

            return [
                'manager' => $manager->name,
                'active_leads' => $activeLeads,
                'active_policies' => $activePolicies,
                'overdue_payments' => $overduePayments,
                'claims' => $claims,
            ];
        });

        $topPolicies = $rows->sortByDesc('active_policies')->first();
        $topLeads = $rows->sortByDesc('active_leads')->first();
        $topOverdue = $rows->sortByDesc('overdue_payments')->first();
        $topClaims = $rows->sortByDesc('claims')->first();

        return [
            Stat::make('Найбільше активних полісів', (string) ($topPolicies['active_policies'] ?? 0))
                ->description($topPolicies ? $topPolicies['manager'] : '—')
                ->icon('heroicon-o-shield-check')
                ->color('success'),

            Stat::make('Найбільше активних заявок', (string) ($topLeads['active_leads'] ?? 0))
                ->description($topLeads ? $topLeads['manager'] : '—')
                ->icon('heroicon-o-inbox')
                ->color('warning'),

            Stat::make('Найбільше прострочених оплат', (string) ($topOverdue['overdue_payments'] ?? 0))
                ->description($topOverdue ? $topOverdue['manager'] : '—')
                ->icon('heroicon-o-exclamation-triangle')
                ->color(($topOverdue['overdue_payments'] ?? 0) > 0 ? 'danger' : 'gray'),

            Stat::make('Найбільше страхових випадків', (string) ($topClaims['claims'] ?? 0))
                ->description($topClaims ? $topClaims['manager'] : '—')
                ->icon('heroicon-o-shield-exclamation')
                ->color('info'),
        ];
    }
}