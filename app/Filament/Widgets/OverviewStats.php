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

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && $user->isManager();
    }

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User || ! $user->isManager()) {
            return [];
        }

        $clientsCount = Client::query()
            ->where('assigned_user_id', $user->id)
            ->count();

        $policiesCount = Policy::query()
            ->where('agent_id', $user->id)
            ->count();

        $claimsCount = Claim::query()
            ->whereHas('policy', fn ($query) => $query->where('agent_id', $user->id))
            ->count();

        $overduePaymentsCount = PolicyPayment::query()
            ->where('status', 'overdue')
            ->whereHas('policy', fn ($query) => $query->where('agent_id', $user->id))
            ->count();

        return [
            Stat::make('Мої клієнти', (string) $clientsCount)
                ->description('Усі закріплені за вами')
                ->icon('heroicon-o-users'),

            Stat::make('Мої поліси', (string) $policiesCount)
                ->description('Усі ваші поліси')
                ->icon('heroicon-o-document-text'),

            Stat::make('Мої страхові випадки', (string) $claimsCount)
                ->description('Усі кейси по ваших полісах')
                ->icon('heroicon-o-shield-exclamation'),

            Stat::make('Прострочені оплати', (string) $overduePaymentsCount)
                ->description('Потребують уваги')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}