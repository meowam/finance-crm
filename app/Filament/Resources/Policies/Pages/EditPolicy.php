<?php

namespace App\Filament\Resources\Policies\Pages;

use App\Filament\Resources\Policies\PolicyResource;
use App\Models\Client;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->can('update', $this->record), 403);

        if ($user->isManager()) {
            $data['agent_id'] = $user->id;

            if (! empty($data['client_id'])) {
                $client = Client::query()->find($data['client_id']);

                if (! $client || ! $client->isVisibleTo($user)) {
                    abort(403);
                }
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