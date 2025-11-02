<?php

namespace App\Filament\Resources\Policies\Pages;

use App\Filament\Resources\Policies\PolicyResource;
use App\Models\Policy;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class CreatePolicy extends CreateRecord
{
    protected static string $resource = PolicyResource::class;
    protected static ?string $title   = 'Створити поліс';

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Створити');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Зберегти та створити наступний запис');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function hasCreateAnother(): bool
    {
        return true;
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Назад')
            ->url(static::getResource()::getUrl('index'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $state    = $this->form->getState();
        $payments = $state['payments'] ?? ($data['payments'] ?? []);

        if (!empty($payments)) {
            $first         = $payments[0] ?? null;
            $paymentStatus = $first['status'] ?? 'draft';
            $data['status'] = $paymentStatus === 'paid' ? 'active' : 'draft';
        } else {
            $data['status'] = 'draft';
        }

        return $data;
    }


    protected function handleRecordCreation(array $data): Model
    {
        Policy::$suppressAutoDraft = true;

        try {
            /** @var \App\Models\Policy $record */
            $record = static::getModel()::create($data);
        } finally {
            Policy::$suppressAutoDraft = false;
        }

        return $record;
    }

    protected function afterCreate(): void
    {
        /** @var \App\Models\Policy $policy */
        $policy  = $this->record->refresh();

        $payment = $policy->payments()->latest('id')->first();

        if ($payment) {
            $policy->forceFill([
                'status' => ($payment->status->value === 'paid') ? 'active' : 'draft',
            ])->save();
            return;
        }

        $policy->payments()->create([
            'amount'   => $policy->premium_amount,
            'method'   => 'no_method',
            'status'   => 'draft',
            'due_date' => now()->addDays(rand(5, 7))->toDateString(),
        ]);

        $policy->forceFill(['status' => 'draft'])->save();
    }
}
