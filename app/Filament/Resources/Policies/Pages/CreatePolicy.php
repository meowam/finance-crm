<?php

namespace App\Filament\Resources\Policies\Pages;

use App\Filament\Resources\Policies\PolicyResource;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreatePolicy extends CreateRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $title = 'Створити поліс';

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

    protected function ensureValidAgent(array $data, User $user): array
    {
        if ($user->isManager()) {
            $data['agent_id'] = $user->id;

            return $data;
        }

        $agentId = isset($data['agent_id']) ? (int) $data['agent_id'] : 0;

        $isValidManager = $agentId > 0
            && User::query()
                ->whereKey($agentId)
                ->where('role', 'manager')
                ->where('is_active', true)
                ->exists();

        if (! $isValidManager) {
            throw ValidationException::withMessages([
                'agent_id' => 'Можна призначити лише активного менеджера.',
            ]);
        }

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user?->can('create', Policy::class), 403);
        abort_unless($user instanceof User, 403);

        $data = $this->ensureValidAgent($data, $user);

        if ($user->isManager() && ! empty($data['client_id'])) {
            $client = Client::query()->find($data['client_id']);

            if (! $client || ! $client->isVisibleTo($user)) {
                abort(403);
            }
        }

        if (blank($data['status'] ?? null)) {
            $data['status'] = 'draft';
        }

        if (blank($data['payment_due_at'] ?? null)) {
            $base = $data['effective_date'] ?? now()->toDateString();
            $data['payment_due_at'] = Carbon::parse($base)->addDays(7)->toDateString();
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
        $policy = $this->record->refresh();

        $payment = $policy->payments()->latest('id')->first();

        if (! $payment) {
            $base = $policy->effective_date ?: now()->toDateString();

            $policy->payments()->create([
                'amount'   => $policy->premium_amount,
                'method'   => 'no_method',
                'status'   => 'draft',
                'due_date' => Carbon::parse($base)->addDays(7)->toDateString(),
            ]);
        }

        $policy->refresh()->recomputeStatus();
    }
}