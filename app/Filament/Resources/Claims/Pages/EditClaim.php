<?php

namespace App\Filament\Resources\Claims\Pages;

use App\Filament\Resources\Claims\ClaimResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditClaim extends EditRecord
{
    protected static string $resource = ClaimResource::class;

    protected static ?string $title = 'Редагувати заяву';

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