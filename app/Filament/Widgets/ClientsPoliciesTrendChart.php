<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ClientsPoliciesTrendChart extends ChartWidget
{
    protected ?string $heading = 'Динаміка за останні 6 місяців';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'lg'      => 8,
    ];

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && $user->isManager();
    }

    protected function getData(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        $labels = [];
        $clientsData = [];
        $policiesData = [];

        foreach (range(5, 0) as $monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            $labels[] = $date->translatedFormat('M Y');

            $clientsQuery = Client::query()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month);

            $policiesQuery = Policy::query()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month);

            if ($user instanceof User && $user->isManager()) {
                $clientsQuery->where('assigned_user_id', $user->id);
                $policiesQuery->where('agent_id', $user->id);
            }

            $clientsData[] = $clientsQuery->count();
            $policiesData[] = $policiesQuery->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Мої нові клієнти',
                    'data' => $clientsData,
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Мої нові поліси',
                    'data' => $policiesData,
                    'fill' => false,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}