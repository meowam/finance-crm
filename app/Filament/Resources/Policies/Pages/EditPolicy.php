<?php

namespace App\Filament\Resources\Policies\Pages;

use App\Filament\Resources\Policies\PolicyResource;
use App\Models\Client;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->can('update', $this->record), 403);

        $data = $this->ensureValidAgent($data, $user);

        if ($user->isManager() && ! empty($data['client_id'])) {
            $client = Client::query()->find($data['client_id']);

            if (! $client || ! $client->isVisibleTo($user)) {
                abort(403);
            }
        }

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