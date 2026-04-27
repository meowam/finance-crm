<?php

namespace App\Filament\Widgets;

use App\Models\PasswordResetRequest;
use App\Models\PolicyPayment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AdminSystemStats extends BaseWidget
{
    protected ?string $heading = 'Системний стан CRM';

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

        return $user instanceof User && $user->isAdmin();
    }

    protected function getStats(): array
    {
        $activeUsers24h = User::query()
            ->where('is_active', true)
            ->where('last_login_at', '>=', now()->subDay())
            ->count();

        $activeManagers24h = User::query()
            ->where('role', 'manager')
            ->where('is_active', true)
            ->where('last_login_at', '>=', now()->subDay())
            ->count();

        $pendingPasswordRequests = PasswordResetRequest::query()
            ->where('status', 'pending')
            ->count();

        $refundedPaymentsQuery = PolicyPayment::query()
            ->where('status', 'refunded');

        $refundedCount = (clone $refundedPaymentsQuery)->count();
        $refundedSum = (float) ((clone $refundedPaymentsQuery)->sum('amount') ?: 0);

        return [
            Stat::make('Активні користувачі', (string) $activeUsers24h)
                ->description('За останні 24 години')
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make('Активні менеджери', (string) $activeManagers24h)
                ->description('Входили за 24 години')
                ->icon('heroicon-o-user-group')
                ->color('info'),

            Stat::make('Зміна пароля', (string) $pendingPasswordRequests)
                ->description('Очікують обробки')
                ->icon('heroicon-o-key')
                ->color($pendingPasswordRequests > 0 ? 'warning' : 'gray'),

            Stat::make('Повернення оплат', (string) $refundedCount)
                ->description('Сума: ' . number_format($refundedSum, 2, ',', ' ') . ' ₴')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color($refundedCount > 0 ? 'danger' : 'gray'),
        ];
    }
}