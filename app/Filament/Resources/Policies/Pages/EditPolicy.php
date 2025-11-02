<?php

namespace App\Filament\Resources\Policies\Pages;

use App\Filament\Resources\Policies\PolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPolicy extends EditRecord
{
    protected static string $resource = PolicyResource::class;
    protected static ?string $title   = 'Редагувати поліс';
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
