<?php

namespace App\Filament\Resources\Claims\Pages;

use App\Enums\ClaimStatus;
use App\Enums\PolicyStatus;
use App\Filament\Resources\Claims\ClaimResource;
use App\Models\Claim;
use App\Models\Policy;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateClaim extends CreateRecord
{
    protected static string $resource = ClaimResource::class;

    protected static ?string $title = 'Створити заяву';

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

    protected function normalizeNotesPayload(array $data, User $user): array
    {
        if (! isset($data['notes']) || ! is_array($data['notes'])) {
            return $data;
        }

        $normalized = [];

        foreach ($data['notes'] as $key => $noteData) {
            if (! is_array($noteData)) {
                continue;
            }

            unset($noteData['id']);
            $noteData['user_id'] = $user->id;

            $normalized[$key] = $noteData;
        }

        $data['notes'] = $normalized;

        return $data;
    }

    protected function normalizeMoneyValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    protected function validateClaimAmounts(array $data, ?Policy $policy): void
    {
        $amountClaimed = $this->normalizeMoneyValue($data['amount_claimed'] ?? 0);
        $amountReserve = $this->normalizeMoneyValue($data['amount_reserve'] ?? 0);
        $amountPaid = $this->normalizeMoneyValue($data['amount_paid'] ?? 0);
        $status = ClaimStatus::normalize($data['status'] ?? '');

        $coverageAmount = $policy?->coverage_amount !== null
            ? (float) $policy->coverage_amount
            : null;

        $errors = [];

        if ($amountClaimed <= 0) {
            $errors['amount_claimed'] = 'Заявлена сума повинна бути більшою за 0.';
        }

        if ($amountReserve < 0) {
            $errors['amount_reserve'] = 'Резервна сума не може бути відʼємною.';
        }

        if ($amountPaid < 0) {
            $errors['amount_paid'] = 'Виплачена сума не може бути відʼємною.';
        }

        if ($coverageAmount !== null && $amountClaimed > $coverageAmount) {
            $errors['amount_claimed'] = 'Заявлена сума не може перевищувати суму покриття поліса.';
        }

        if ($coverageAmount !== null && $amountReserve > $coverageAmount) {
            $errors['amount_reserve'] = 'Резервна сума не може перевищувати суму покриття поліса.';
        }

        if ($amountPaid > $amountReserve) {
            $errors['amount_paid'] = 'Виплачена сума не може перевищувати резервну суму.';
        }

        if ($status === ClaimStatus::Paid->value && $amountPaid <= 0) {
            $errors['amount_paid'] = 'Для статусу «Виплачено» потрібно вказати суму виплати.';
        }

        if ($status === ClaimStatus::Rejected->value && $amountPaid > 0) {
            $errors['amount_paid'] = 'Для відхиленої заяви виплачена сума повинна дорівнювати 0.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function ensureClaimPolicyIsValid(?int $policyId, User $user, ?string $lossOccurredAt = null): ?Policy
    {
        if (! $policyId) {
            return null;
        }

        $policy = Policy::query()->find($policyId);

        if (! $policy || ! $policy->isVisibleTo($user)) {
            abort(403);
        }

        $status = $policy->status instanceof PolicyStatus
            ? $policy->status->value
            : (string) $policy->status;

        if (! in_array($status, [PolicyStatus::Active->value, PolicyStatus::Completed->value], true)) {
            throw ValidationException::withMessages([
                'policy_id' => 'Страховий випадок можна створити лише для активного або завершеного поліса.',
            ]);
        }

        if ($lossOccurredAt && $policy->effective_date && $policy->expiration_date) {
            $lossDate = Carbon::parse($lossOccurredAt)->startOfDay();
            $effectiveDate = $policy->effective_date->copy()->startOfDay();
            $expirationDate = $policy->expiration_date->copy()->startOfDay();

            if ($lossDate->lt($effectiveDate) || $lossDate->gt($expirationDate)) {
                throw ValidationException::withMessages([
                    'loss_occurred_at' => 'Дата страхового випадку повинна бути в межах строку дії поліса.',
                ]);
            }
        }

        return $policy;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->can('create', Claim::class), 403);

        $data['reported_by_id'] = $user->id;
        $data = $this->normalizeNotesPayload($data, $user);

        $policy = $this->ensureClaimPolicyIsValid(
            isset($data['policy_id']) ? (int) $data['policy_id'] : null,
            $user,
            $data['loss_occurred_at'] ?? null,
        );

        $this->validateClaimAmounts($data, $policy);

        return $data;
    }
}