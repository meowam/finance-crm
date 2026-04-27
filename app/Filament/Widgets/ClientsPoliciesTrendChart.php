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

    protected static ?int $sort = 20;

    protected ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 1,
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
                    'tension' => 0.35,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.18)',
                    'pointBackgroundColor' => '#22c55e',
                    'pointBorderColor' => '#22c55e',
                    'pointRadius' => 4,
                    'pointHoverRadius' => 5,
                    'borderWidth' => 3,
                ],
                [
                    'label' => 'Мої нові поліси',
                    'data' => $policiesData,
                    'fill' => false,
                    'tension' => 0.35,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.18)',
                    'pointBackgroundColor' => '#f59e0b',
                    'pointBorderColor' => '#f59e0b',
                    'pointRadius' => 4,
                    'pointHoverRadius' => 5,
                    'borderWidth' => 3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'layout' => [
                'padding' => [
                    'top' => 8,
                    'right' => 8,
                    'bottom' => 0,
                    'left' => 0,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'align' => 'start',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'boxWidth' => 8,
                        'boxHeight' => 8,
                        'padding' => 28,
                    ],
                ],
            ],
            'elements' => [
                'line' => [
                    'borderJoinStyle' => 'round',
                ],
            ],
            'scales' => [
                'x' => [
                    'offset' => false,
                    'grid' => [
                        'display' => false,
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'maxRotation' => 30,
                        'minRotation' => 30,
                        'autoSkip' => false,
                        'padding' => 6,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'precision' => 0,
                        'padding' => 6,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}