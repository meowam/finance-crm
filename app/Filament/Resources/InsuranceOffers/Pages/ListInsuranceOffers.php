<?php

namespace App\Filament\Resources\InsuranceOffers\Pages;

use App\Filament\Resources\InsuranceOffers\InsuranceOfferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsuranceOffers extends ListRecords
{
    protected static string $resource = InsuranceOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
