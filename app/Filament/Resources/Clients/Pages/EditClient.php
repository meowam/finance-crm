<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    public function getTitle(): string
    {
        return 'Редагувати клієнта';
    }

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

    protected function ensureValidAssignedManager(array $data, User $user): array
    {
        if ($user->isManager()) {
            $data['assigned_user_id'] = $user->id;

            return $data;
        }

        $assignedUserId = isset($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : 0;

        $isValidManager = $assignedUserId > 0
            && User::query()
                ->whereKey($assignedUserId)
                ->where('role', 'manager')
                ->where('is_active', true)
                ->exists();

        if (! $isValidManager) {
            throw ValidationException::withMessages([
                'assigned_user_id' => 'Можна призначити лише активного менеджера.',
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

        $data = $this->ensureValidAssignedManager($data, $user);

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Зберегти зміни')
                ->submit('save'),

            Actions\Action::make('cancel')
                ->label('Скасувати')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    protected function getHeaderActions(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        /** @var Client $client */
        $client = $this->record;

        return [
            DeleteAction::make()
                ->label('Видалити')
                ->visible($user instanceof User && $user->can('delete', $client))
                ->requiresConfirmation()
                ->modalDescription(fn () => $client->hasDeletionHistory()
                    ? 'У клієнта є пов’язані історичні записи. Він буде архівований через soft delete та зникне з активних списків, але історія збережеться.'
                    : 'У клієнта немає пов’язаних історичних записів. Він буде видалений з бази назавжди.')
                ->successNotificationTitle(fn () => $client->hasDeletionHistory()
                    ? 'Клієнта архівовано'
                    : 'Клієнта видалено назавжди')
                ->action(function () use ($client): void {
                    $client->archiveOrDelete();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
}