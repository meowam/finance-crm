<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ClientSourceStats extends BaseWidget
{
    protected ?string $heading = 'Клієнти за останні 30 днів';

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            '2xl' => 4,
        ];
    }

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        $baseQuery = Client::query()
            ->where('created_at', '>=', now()->subDays(30));

        if ($user?->isManager()) {
            $baseQuery->where('assigned_user_id', $user->id);
        }

        $newClientsCount = (clone $baseQuery)->count();
        $onlineClientsCount = (clone $baseQuery)->where('source', 'online')->count();
        $officeClientsCount = (clone $baseQuery)->where('source', 'office')->count();
        $recommendationClientsCount = (clone $baseQuery)->where('source', 'recommendation')->count();

        return [
            Stat::make('Нові клієнти', (string) $newClientsCount)
                ->description('Усі канали')
                ->icon('heroicon-o-user-plus'),

            Stat::make('Через офіс', (string) $officeClientsCount)
                ->description('Офісні звернення')
                ->icon('heroicon-o-briefcase')
                ->color('info'),

            Stat::make('Онлайн', (string) $onlineClientsCount)
                ->description('Онлайн-звернення')
                ->icon('heroicon-o-globe-alt')
                ->color('success'),

            Stat::make('За рекомендацією', (string) $recommendationClientsCount)
                ->description('Рекомендації')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('warning'),
        ];
    }
}