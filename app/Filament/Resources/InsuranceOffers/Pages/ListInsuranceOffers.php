<?php

namespace App\Filament\Resources\InsuranceOffers\Pages;

use App\Filament\Resources\InsuranceOffers\InsuranceOfferResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListInsuranceOffers extends ListRecords
{
    protected static string $resource = InsuranceOfferResource::class;

    protected static ?string $title = 'Страхові пропозиції';

    protected function getHeaderActions(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        return [
            CreateAction::make()
                ->visible($user instanceof User && ! $user->isManager()),
        ];
    }
}