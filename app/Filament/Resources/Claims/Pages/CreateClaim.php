<?php

namespace App\Filament\Resources\Claims\Pages;

use App\Filament\Resources\Claims\ClaimResource;
use App\Models\Claim;
use App\Models\Policy;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
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

    protected function ensureClaimPolicyIsValid(?int $policyId, User $user, ?string $lossOccurredAt = null): void
{
    if (! $policyId) {
        return;
    }

    $policy = Policy::query()->find($policyId);

    if (! $policy || ! $policy->isVisibleTo($user)) {
        abort(403);
    }

    if ((string) $policy->status->value !== 'active') {
        throw ValidationException::withMessages([
            'policy_id' => 'Страховий випадок можна створити лише для активного поліса.',
        ]);
    }

    if ($lossOccurredAt && $policy->effective_date && $policy->expiration_date) {
        $lossDate = \Illuminate\Support\Carbon::parse($lossOccurredAt)->startOfDay();
        $effectiveDate = $policy->effective_date->copy()->startOfDay();
        $expirationDate = $policy->expiration_date->copy()->startOfDay();

        if ($lossDate->lt($effectiveDate) || $lossDate->gt($expirationDate)) {
            throw ValidationException::withMessages([
                'loss_occurred_at' => 'Дата страхового випадку повинна бути в межах строку дії поліса.',
            ]);
        }
    }
}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->can('create', Claim::class), 403);

        $data['reported_by_id'] = $user->id;
        $data = $this->normalizeNotesPayload($data, $user);

        $this->ensureClaimPolicyIsValid(
    isset($data['policy_id']) ? (int) $data['policy_id'] : null,
    $user,
    $data['loss_occurred_at'] ?? null,
);

        return $data;
    }
}