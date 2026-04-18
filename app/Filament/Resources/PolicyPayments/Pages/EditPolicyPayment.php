<?php

namespace App\Filament\Resources\PolicyPayments\Pages;

use App\Filament\Resources\PolicyPayments\PolicyPaymentResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPolicyPayment extends EditRecord
{
    protected static string $resource = PolicyPaymentResource::class;

    protected static ?string $title = 'Редагувати оплату полісу';

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

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        /** @var User|null $user */
        $user = Auth::user();

        if (
            $user instanceof User &&
            $user->isManager() &&
            (int) optional($this->record->policy)->agent_id !== (int) $user->id
        ) {
            abort(403);
        }
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
        /** @var User|null $user */
        $user = Auth::user();

        return [
            DeleteAction::make()
                ->visible(fn () => ! $this->isLocked() && $user instanceof User && ! $user->isManager()),
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

        /** @var User|null $user */
        $user = Auth::user();

        if ($user instanceof User && $user->isManager()) {
            $policy = $this->record->policy;

            if (! $policy || (int) $policy->agent_id !== (int) $user->id) {
                abort(403);
            }
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