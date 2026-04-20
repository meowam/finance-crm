<?php

namespace App\Filament\Resources\Policies\Pages;

use App\Filament\Resources\Policies\PolicyResource;
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

        abort_unless(Auth::user()?->can('update', $this->record), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(Auth::user()?->can('delete', $this->record) ?? false),
        ];
    }
}