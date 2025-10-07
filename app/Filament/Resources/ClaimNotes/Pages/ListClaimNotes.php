<?php

namespace App\Filament\Resources\ClaimNotes\Pages;

use App\Filament\Resources\ClaimNotes\ClaimNoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClaimNotes extends ListRecords
{
    protected static string $resource = ClaimNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
