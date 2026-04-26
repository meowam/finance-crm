<?php
namespace App\Filament\Widgets;

use App\Models\Policy;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ManagerPoliciesChart extends ChartWidget
{
    protected ?string $heading = 'Поліси по менеджерах';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && $user->isSupervisor();
    }

    protected function getData(): array
    {
        $managers = User::query()
            ->where('role', 'manager')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $labels = [];
        $data   = [];

        foreach ($managers as $manager) {
            $labels[] = $manager->name;
            $data[]   = Policy::query()
                ->where('agent_id', $manager->id)
                ->where('status', 'active')
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Активні поліси',
                    'data'  => $data,
                ],
            ],
            'labels'   => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
