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
            Stat::make('Активні користувачі за 24 год', (string) $activeUsers24h)
                ->description('Усі ролі')
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make('Активні менеджери за 24 год', (string) $activeManagers24h)
                ->description('Менеджери, які входили в систему')
                ->icon('heroicon-o-user-group')
                ->color('info'),

            Stat::make('Запити на зміну пароля', (string) $pendingPasswordRequests)
                ->description('Очікують обробки адміністратором')
                ->icon('heroicon-o-key')
                ->color($pendingPasswordRequests > 0 ? 'warning' : 'gray'),

            Stat::make('Повернення оплат', $refundedCount . ' / ' . number_format($refundedSum, 2, ',', ' ') . ' ₴')
                ->description('Оплати зі статусом refunded')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color($refundedCount > 0 ? 'danger' : 'gray'),
        ];
    }
}