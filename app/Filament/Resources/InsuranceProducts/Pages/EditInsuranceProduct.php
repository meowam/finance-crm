<?php

namespace App\Filament\Resources\InsuranceProducts\Pages;

use App\Filament\Resources\InsuranceProducts\InsuranceProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInsuranceProduct extends EditRecord
{
    protected static string $resource = InsuranceProductResource::class;
    protected static ?string $title   = 'Редагувати страховий продукт';
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
