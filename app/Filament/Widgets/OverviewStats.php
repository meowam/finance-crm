<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class OverviewStats extends BaseWidget
{
    protected ?string $heading = 'Загальна статистика';

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        $clientsQuery = Client::query();
        $policiesQuery = Policy::query();
        $activePoliciesQuery = Policy::query()->where('status', 'active');
        $expiringPoliciesQuery = Policy::query()
            ->whereBetween('expiration_date', [
                now()->toDateString(),
                now()->addDays(30)->toDateString(),
            ]);

        if ($user?->isManager()) {
            $clientsQuery->where('assigned_user_id', $user->id);
            $policiesQuery->where('agent_id', $user->id);
            $activePoliciesQuery->where('agent_id', $user->id);
            $expiringPoliciesQuery->where('agent_id', $user->id);
        }

        return [
            Stat::make('Клієнти', (string) $clientsQuery->count())
                ->description('Усього у системі')
                ->icon('heroicon-o-users'),

            Stat::make('Поліси', (string) $policiesQuery->count())
                ->description('Усього оформлено')
                ->icon('heroicon-o-document-text'),

            Stat::make('Активні поліси', (string) $activePoliciesQuery->count())
                ->description('Поточний статус: active')
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Скоро завершуються', (string) $expiringPoliciesQuery->count())
                ->description('Закінчуються протягом 30 днів')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning'),
        ];
    }
}