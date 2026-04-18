<?php

namespace App\Notifications;

use App\Filament\Resources\Policies\PolicyResource;
use App\Models\Policy;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PolicyExpiringSoonNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Policy $policy,
        protected int $daysLeft,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $client = $this->policy->client;

        $clientName = $client
            ? trim(implode(' ', array_filter([
                $client->last_name,
                $client->first_name,
                $client->middle_name,
            ])))
            : 'Клієнт';

        return [
            'title' => "Поліс {$this->policy->policy_number} скоро завершується",
            'body' => "Клієнт: {$clientName}. До завершення залишилося {$this->daysLeft} дн. Дата завершення: {$this->policy->expiration_date?->format('d.m.Y')}.",
            'notification_type' => 'policy_expiring',
            'policy_id' => $this->policy->id,
            'policy_number' => $this->policy->policy_number,
            'policy_url' => PolicyResource::getUrl('edit', ['record' => $this->policy->id]),
            'client_id' => $client?->id,
            'client_name' => $clientName,
            'expires_at' => $this->policy->expiration_date?->format('Y-m-d'),
            'days_left' => $this->daysLeft,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}