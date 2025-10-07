<?php

namespace App\Filament\Resources\InsuranceOffers\Pages;

use App\Filament\Resources\InsuranceOffers\InsuranceOfferResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInsuranceOffer extends EditRecord
{
    protected static string $resource = InsuranceOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
