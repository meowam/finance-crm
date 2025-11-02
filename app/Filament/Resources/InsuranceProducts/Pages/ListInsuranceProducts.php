<?php

namespace App\Filament\Resources\InsuranceProducts\Pages;

use App\Filament\Resources\InsuranceProducts\InsuranceProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsuranceProducts extends ListRecords
{
    protected static string $resource = InsuranceProductResource::class;
    protected static ?string $title   = 'Страхові продукти';
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
