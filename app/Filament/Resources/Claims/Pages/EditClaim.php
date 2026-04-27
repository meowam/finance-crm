<?php

namespace App\Filament\Resources\Claims\Pages;

use App\Filament\Resources\Claims\ClaimResource;
use App\Models\Policy;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditClaim extends EditRecord
{
    protected static string $resource = ClaimResource::class;

    protected static ?string $title = 'Редагувати заяву';

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

    protected function normalizeNotesPayload(array $data, User $user): array
    {
        if (! isset($data['notes']) || ! is_array($data['notes'])) {
            return $data;
        }

        $existingNotes = $this->record->notes()
            ->get()
            ->keyBy('id');

        $normalized = [];

        foreach ($data['notes'] as $key => $noteData) {
            if (! is_array($noteData)) {
                continue;
            }

            $noteId = isset($noteData['id']) ? (int) $noteData['id'] : 0;

            if ($noteId > 0 && $existingNotes->has($noteId)) {
                $noteData['user_id'] = $existingNotes->get($noteId)->user_id;
            } else {
                unset($noteData['id']);
                $noteData['user_id'] = $user->id;
            }

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
        $status = (string) ($data['status'] ?? '');

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

        if ($status === 'виплачено' && $amountPaid <= 0) {
            $errors['amount_paid'] = 'Для статусу «Виплачено» потрібно вказати суму виплати.';
        }

        if ($status === 'відхилено' && $amountPaid > 0) {
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->can('update', $this->record), 403);

        $policy = $this->ensureClaimPolicyIsValid(
            isset($data['policy_id']) ? (int) $data['policy_id'] : null,
            $user,
            $data['loss_occurred_at'] ?? null,
        );

        $this->validateClaimAmounts($data, $policy);

        $data['reported_by_id'] = (int) $this->record->reported_by_id;
        $data = $this->normalizeNotesPayload($data, $user);

        return $data;
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