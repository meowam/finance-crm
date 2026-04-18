<?php

namespace App\Console\Commands;

use App\Models\Policy;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\PolicyExpiringSoonNotification;
use Illuminate\Console\Command;

class SendPolicyExpiryNotifications extends Command
{
    protected $signature = 'policies:notify-expiring';
    protected $description = 'Надіслати менеджерам сповіщення про поліси, строк дії яких скоро завершується';

    public function handle(): int
    {
        $policies = Policy::query()
            ->with(['client', 'agent'])
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '>=', now()->toDateString())
            ->whereDate('expiration_date', '<=', now()->addDays(30)->toDateString())
            ->get();

        $this->info('Полісів у межах 30 днів: ' . $policies->count());

        foreach ($policies as $policy) {
            /** @var User|null $manager */
            $manager = $policy->agent;

            if (! $manager || ! $manager->is_active) {
                $this->warn("Пропуск {$policy->policy_number}: немає активного менеджера");
                continue;
            }

            $daysLeft = now()->startOfDay()->diffInDays($policy->expiration_date, false);

            if ($daysLeft < 0 || $daysLeft > 30) {
                continue;
            }

            $existingNotification = UserNotification::query()
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $manager->id)
                ->where('type', PolicyExpiringSoonNotification::class)
                ->get()
                ->first(function ($notification) use ($policy) {
                    return (int) ($notification->data['policy_id'] ?? 0) === (int) $policy->id;
                });

            if ($existingNotification) {
                $this->line("Уже існує сповіщення для {$policy->policy_number}");
                continue;
            }

            $manager->notify(new PolicyExpiringSoonNotification($policy, $daysLeft));

            $this->info("Створено сповіщення: {$policy->policy_number} -> {$manager->email} ({$daysLeft} дн.)");
        }

        $this->info('Готово.');

        return self::SUCCESS;
    }
}