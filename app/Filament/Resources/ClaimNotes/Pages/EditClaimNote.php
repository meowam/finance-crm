<?php

namespace App\Filament\Resources\ClaimNotes\Pages;

use App\Filament\Resources\ClaimNotes\ClaimNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClaimNote extends EditRecord
{
    protected static string $resource = ClaimNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
