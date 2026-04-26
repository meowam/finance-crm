<?php
namespace App\Filament\Widgets;

use App\Models\Policy;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PolicyStatusChart extends ChartWidget
{
    protected ?string $heading = 'Статуси полісів';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'lg'      => 4,
    ];

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        return $user instanceof \App\Models\User  && $user->isManager();
    }

    protected function getData(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        $baseQuery = Policy::query();

        if ($user?->isManager()) {
            $baseQuery->where('agent_id', $user->id);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Поліси',
                    'data'  => [
                        (clone $baseQuery)->where('status', 'draft')->count(),
                        (clone $baseQuery)->where('status', 'active')->count(),
                        (clone $baseQuery)->where('status', 'completed')->count(),
                        (clone $baseQuery)->where('status', 'canceled')->count(),
                    ],
                ],
            ],
            'labels'   => [
                'Чернетка',
                'Активний',
                'Завершено',
                'Скасовано',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
