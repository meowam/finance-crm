<?php

namespace App\Notifications;

use App\Models\LeadRequest;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewLeadAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected LeadRequest $leadRequest,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $leadLabel = $this->leadRequest->display_label ?: ('Lead #' . $this->leadRequest->id);

        $sourceLabel = match ($this->leadRequest->source) {
            'office' => 'Офіс',
            'online' => 'Онлайн',
            'recommendation' => 'Рекомендація',
            'landing' => 'Лендінг',
            'other' => 'Інше',
            default => (string) $this->leadRequest->source,
        };

        $leadUrl = "/admin/lead-requests/{$this->leadRequest->id}/edit";

        return [
            ...FilamentNotification::make()
                ->title('Нова вхідна заявка')
                ->body("Новий лід: {$leadLabel}. Джерело: {$sourceLabel}.")
                ->icon('heroicon-o-inbox-stack')
                ->info()
                ->actions([
                    Action::make('open')
                        ->label('Відкрити')
                        ->url($leadUrl)
                        ->markAsRead(),
                ])
                ->getDatabaseMessage(),

            'notification_type' => 'lead_assigned',
            'lead_request_id' => $this->leadRequest->id,
            'lead_label' => $leadLabel,
            'source' => $this->leadRequest->source,
            'phone' => $this->leadRequest->phone,
            'email' => $this->leadRequest->email,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}