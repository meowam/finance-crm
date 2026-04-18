<?php

namespace App\Filament\Resources\InsuranceProducts\Pages;

use App\Filament\Resources\InsuranceProducts\InsuranceProductResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListInsuranceProducts extends ListRecords
{
    protected static string $resource = InsuranceProductResource::class;

    protected static ?string $title = 'Страхові продукти';

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