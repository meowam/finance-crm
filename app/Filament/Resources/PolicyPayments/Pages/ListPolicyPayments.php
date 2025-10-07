<?php

namespace App\Filament\Resources\PolicyPayments\Pages;

use App\Filament\Resources\PolicyPayments\PolicyPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPolicyPayments extends ListRecords
{
    protected static string $resource = PolicyPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
