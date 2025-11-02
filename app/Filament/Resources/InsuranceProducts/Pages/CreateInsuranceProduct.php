<?php

namespace App\Filament\Resources\InsuranceProducts\Pages;

use App\Filament\Resources\InsuranceProducts\InsuranceProductResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreateInsuranceProduct extends CreateRecord
{
    protected static string $resource = InsuranceProductResource::class;
    protected static ?string $title   = 'Створити страховий продукт';

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Створити');
    }
    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Зберегти та створити наступний запис');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function hasCreateAnother(): bool
    {
        return true;
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Назад')
            ->url(static::getResource()::getUrl('index'));
    }
}
