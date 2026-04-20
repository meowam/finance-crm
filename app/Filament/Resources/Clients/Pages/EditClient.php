<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
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

        abort_unless(Auth::user()?->can('update', $this->record), 403);
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
        return [
            DeleteAction::make()
                ->visible(Auth::user()?->can('delete', $this->record) ?? false),
        ];
    }
}