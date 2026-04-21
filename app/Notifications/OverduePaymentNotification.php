<?php

namespace App\Notifications;

use App\Models\PolicyPayment;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OverduePaymentNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected PolicyPayment $payment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $policy = $this->payment->policy;
        $client = $policy?->client;

        $clientName = $client
            ? ($client->type === 'company' && filled($client->company_name)
                ? $client->company_name
                : trim(implode(' ', array_filter([
                    $client->last_name,
                    $client->first_name,
                    $client->middle_name,
                ]))))
            : 'Клієнт';

        if ($clientName === '') {
            $clientName = 'Клієнт';
        }

        $policyUrl = $policy ? "/admin/policies/{$policy->id}/edit" : '/admin/policies';

        return [
            ...FilamentNotification::make()
                ->title("Прострочена оплата за полісом {$policy?->policy_number}")
                ->body("Клієнт: {$clientName}. Сума: {$this->payment->amount}. Строк оплати: {$this->payment->due_date?->format('d.m.Y')}.")
                ->danger()
                ->icon('heroicon-o-exclamation-triangle')
                ->actions([
                    Action::make('open')
                        ->label('Відкрити')
                        ->url($policyUrl)
                        ->markAsRead(),
                ])
                ->getDatabaseMessage(),

            'notification_type' => 'payment_overdue',
            'payment_id' => $this->payment->id,
            'policy_id' => $policy?->id,
            'policy_number' => $policy?->policy_number,
            'policy_url' => $policyUrl,
            'client_id' => $client?->id,
            'client_name' => $clientName,
            'due_date' => $this->payment->due_date?->format('Y-m-d'),
            'amount' => $this->payment->amount,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}