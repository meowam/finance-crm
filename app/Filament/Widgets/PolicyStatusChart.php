<?php

namespace App\Filament\Widgets;

use App\Models\Policy;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PolicyStatusChart extends ChartWidget
{
    protected ?string $heading = 'Статуси полісів';

    protected static ?int $sort = 21;

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

        $baseQuery = Policy::query();

        if ($user?->isManager()) {
            $baseQuery->where('agent_id', $user->id);
        }

        $draftCount = (clone $baseQuery)->where('status', 'draft')->count();
        $activeCount = (clone $baseQuery)->where('status', 'active')->count();
        $completedCount = (clone $baseQuery)->where('status', 'completed')->count();
        $canceledCount = (clone $baseQuery)->where('status', 'canceled')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Поліси',
                    'data' => [
                        $draftCount,
                        $activeCount,
                        $completedCount,
                        $canceledCount,
                    ],
                    'backgroundColor' => [
                        '#f59e0b',
                        '#22c55e',
                        '#3b82f6',
                        '#ef4444',
                    ],
                    'borderColor' => [
                        '#f59e0b',
                        '#22c55e',
                        '#3b82f6',
                        '#ef4444',
                    ],
                    'borderWidth' => 2,
                    'hoverOffset' => 6,
                ],
            ],
            'labels' => [
                'Чернетка',
                'Активний',
                'Завершено',
                'Скасовано',
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'cutout' => '58%',
            'layout' => [
                'padding' => [
                    'top' => 8,
                    'right' => 8,
                    'bottom' => 0,
                    'left' => 8,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'align' => 'center',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'boxWidth' => 10,
                        'padding' => 16,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}