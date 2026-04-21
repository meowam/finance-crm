<?php

namespace App\Filament\Widgets;

use App\Models\Claim;
use App\Models\Client;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class OverviewStats extends BaseWidget
{
    protected ?string $heading = 'Ключові показники';

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        $clientsQuery = Client::query();
        $policiesQuery = Policy::query();
        $claimsQuery = Claim::query();
        $overduePaymentsQuery = PolicyPayment::query()->where('status', 'overdue');

        if ($user?->isManager()) {
            $clientsQuery->where('assigned_user_id', $user->id);
            $policiesQuery->where('agent_id', $user->id);
            $claimsQuery->whereHas('policy', fn ($q) => $q->where('agent_id', $user->id));
            $overduePaymentsQuery->whereHas('policy', fn ($q) => $q->where('agent_id', $user->id));
        }

        if ($user?->isManager()) {
            return [
                Stat::make('Мої клієнти', (string) $clientsQuery->count())
                    ->description('Усі закріплені за вами')
                    ->icon('heroicon-o-users'),

                Stat::make('Мої поліси', (string) $policiesQuery->count())
                    ->description('Усі ваші поліси')
                    ->icon('heroicon-o-document-text'),

                Stat::make('Мої страхові випадки', (string) $claimsQuery->count())
                    ->description('Усі кейси по ваших полісах')
                    ->icon('heroicon-o-shield-exclamation'),

                Stat::make('Прострочені оплати', (string) $overduePaymentsQuery->count())
                    ->description('Потребують уваги')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger'),
            ];
        }

        return [
            Stat::make('Усього клієнтів', (string) $clientsQuery->count())
                ->description('Усі клієнти в системі')
                ->icon('heroicon-o-users'),

            Stat::make('Усього полісів', (string) $policiesQuery->count())
                ->description('Усі оформлені поліси')
                ->icon('heroicon-o-document-text'),

            Stat::make('Страхові випадки', (string) $claimsQuery->count())
                ->description('Усі кейси в роботі та архіві')
                ->icon('heroicon-o-shield-exclamation'),

            Stat::make('Прострочені оплати', (string) $overduePaymentsQuery->count())
                ->description('Потребують уваги')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}