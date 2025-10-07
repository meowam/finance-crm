<?php

namespace App\Filament\Resources\PolicyPayments\Pages;

use App\Filament\Resources\PolicyPayments\PolicyPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPolicyPayment extends EditRecord
{
    protected static string $resource = PolicyPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
