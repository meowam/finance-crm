<?php
namespace App\Filament\Resources\PolicyPayments\Pages;

use App\Filament\Resources\PolicyPayments\PolicyPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPolicyPayment extends EditRecord
{
    protected static string $resource = PolicyPaymentResource::class;
    protected static ?string $title   = 'Редагувати оплату полісу';

    protected function isLocked(): bool
    {
        $status = $this->record->status instanceof \BackedEnum
            ? $this->record->status->value
            : (string) $this->record->status;

        return in_array(mb_strtolower($status), ['paid', 'overdue'], true);
    }

    protected function notifyLock(): void
    {
        Notification::make()
            ->title('ЦЕЙ ЗАПИС НЕМОЖЛИВО ВІДРЕДАГУВАТИ')
            ->body('Платіж має статус сплачено, в обробці або протермінований. Зміни та видалення недоступні.')
            ->icon('heroicon-o-lock-closed')
            ->danger()
            ->persistent()
            ->send();
    }

    public function mount($record): void
    {
        parent::mount($record);

        if ($this->isLocked()) {
            $this->notifyLock();
        }
    }

    public function updated($name, $value): void
    {
        if ($this->isLocked()) {
            $this->notifyLock(); 
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn() => ! $this->isLocked()),
        ];
    }

    protected function getFormActions(): array
    {
        return $this->isLocked()
            ? []
            : parent::getFormActions();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->isLocked()) {
            abort(403, 'Запис заблоковано для редагування.');
        }

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

        $status = $payment->status instanceof \BackedEnum
            ? $payment->status->value
            : (string) $payment->status;

        $map = [
            'paid'      => 'active',
            'scheduled' => 'draft',
            'overdue'   => 'canceled',
            'canceled'  => 'canceled',
        ];

        $newStatus = $map[$status] ?? null;

        if ($newStatus && $policy->status !== $newStatus) {
            $policy->forceFill(['status' => $newStatus])->save();
        }
    }
}
