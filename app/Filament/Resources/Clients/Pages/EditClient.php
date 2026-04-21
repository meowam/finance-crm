<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->can('update', $this->record), 403);

        if ($user->isManager()) {
            $data['assigned_user_id'] = $user->id;
        }

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

        return [
            DeleteAction::make()
                ->visible($user instanceof User && $user->can('delete', $this->record)),
        ];
    }
}