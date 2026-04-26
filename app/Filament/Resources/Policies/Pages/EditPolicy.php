<?php

namespace App\Filament\Resources\Policies\Pages;

use App\Filament\Resources\Policies\PolicyResource;
use App\Models\Policy;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class EditPolicy extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $title = 'Редагувати поліс';

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        abort_unless($user->can('update', $this->record), 403);
    }

    protected function canEditBeforeStart(): bool
    {
        return $this->record instanceof Policy && $this->record->isEditableBeforeStart();
    }

    protected function normalizedStatus(mixed $status): string
    {
        return $status instanceof \BackedEnum
            ? (string) $status->value
            : (string) $status;
    }

    protected function lockImmutableFields(array $data): array
    {
        /** @var Policy $record */
        $record = $this->record;

        return array_merge($data, [
            'client_id' => $record->client_id,
            'insurance_offer_id' => $record->insurance_offer_id,
            'agent_id' => $record->agent_id,
            'premium_amount' => $record->premium_amount !== null ? number_format((float) $record->premium_amount, 2, '.', '') : null,
            'coverage_amount' => $record->coverage_amount !== null ? number_format((float) $record->coverage_amount, 2, '.', '') : null,
            'payment_frequency' => $record->payment_frequency,
            'commission_rate' => $record->commission_rate !== null ? number_format((float) $record->commission_rate, 2, '.', '') : null,
            'notes' => $record->notes,
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->can('update', $this->record), 403);

        /** @var Policy $record */
        $record = $this->record;

        if (! $this->canEditBeforeStart()) {
            throw ValidationException::withMessages([
                'effective_date' => 'Цей поліс уже набрав чинності або був скасований. Редагування недоступне.',
            ]);
        }

        $data = $this->lockImmutableFields($data);

        $newEffectiveDate = $data['effective_date'] ?? $record->effective_date?->toDateString();

        if (! filled($newEffectiveDate)) {
            throw ValidationException::withMessages([
                'effective_date' => 'Вкажіть дату початку дії.',
            ]);
        }

        if (Carbon::parse($newEffectiveDate)->startOfDay()->lessThanOrEqualTo(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'effective_date' => 'Можна встановити лише майбутню дату початку дії.',
            ]);
        }

        $currentStatus = $this->normalizedStatus($record->status);
        $newStatus = $data['status'] ?? $currentStatus;

        if (! in_array($newStatus, [$currentStatus, 'canceled'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Для збереженого поліса можна змінити лише статус на «скасовано».',
            ]);
        }

        $offer = $record->insuranceOffer;

        if (! $offer) {
            throw ValidationException::withMessages([
                'insurance_offer_id' => 'Для поліса не знайдено страховий продукт.',
            ]);
        }

        $data['status'] = $newStatus;
        $data['expiration_date'] = Carbon::parse($newEffectiveDate)
            ->addMonths((int) $offer->duration_months)
            ->format('Y-m-d');

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Policy $record */
        return DB::transaction(function () use ($record, $data): Model {
            $statusBefore = $this->normalizedStatus($record->status);

            $record->update($data);

            $statusAfter = $this->normalizedStatus($record->fresh()->status);

            if ($statusBefore !== 'canceled' && $statusAfter === 'canceled') {
                $record->refresh()->markCanceledWithPaymentSync();
            }

            return $record->refresh();
        });
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Зберегти')
            ->visible($this->canEditBeforeStart());
    }

    protected function getHeaderActions(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        return [
            DeleteAction::make()
                ->visible($user instanceof User && $user->can('delete', $this->record)),
        ];
    }
}