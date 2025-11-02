<?php
namespace App\Filament\Resources\PolicyPayments\Pages;

use App\Filament\Resources\PolicyPayments\PolicyPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPolicyPayment extends EditRecord
{
    protected static string $resource = PolicyPaymentResource::class;
    protected static ?string $title   = 'Редагувати оплату полісу';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === 'paid' && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $payment = $this->record->refresh();
        $policy  = $payment->policy; 

        if (! $policy) {
            return;
        }

        $map = [
            'paid'      => 'active',
            'scheduled' => 'draft',
            'overdue'   => 'canceled',
            'canceled'  => 'canceled',
        ];

        $newStatus = $map[$payment->status] ?? null;

        if ($newStatus && $policy->status !== $newStatus) {
            $policy->forceFill(['status' => $newStatus])->save();
        }
    }
}
