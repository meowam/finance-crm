<?php

namespace App\Notifications;

use App\Models\Client;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewClientAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Client $client,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $clientLabel = $this->client->display_label ?: ('Клієнт #' . $this->client->id);

        $sourceLabel = match ($this->client->source) {
            'office' => 'Офіс',
            'online' => 'Онлайн',
            'recommendation' => 'Рекомендація',
            'landing' => 'Лендінг',
            'other' => 'Інше',
            default => (string) $this->client->source,
        };

        $clientUrl = "/admin/clients/{$this->client->id}/edit";

        return [
            ...FilamentNotification::make()
                ->title('Новий клієнт')
                ->body("До вас призначено нового клієнта: {$clientLabel}. Джерело: {$sourceLabel}.")
                ->icon('heroicon-o-user-plus')
                ->success()
                ->actions([
                    Action::make('open')
                        ->label('Відкрити')
                        ->url($clientUrl)
                        ->markAsRead(),
                ])
                ->getDatabaseMessage(),

            'notification_type' => 'client_assigned',
            'client_id' => $this->client->id,
            'client_label' => $clientLabel,
            'source' => $this->client->source,
            'primary_phone' => $this->client->primary_phone,
            'primary_email' => $this->client->primary_email,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}