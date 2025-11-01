<?php
namespace App\Filament\Resources\InsuranceOffers\Pages;

use App\Filament\Resources\InsuranceOffers\InsuranceOfferResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreateInsuranceOffer extends CreateRecord
{
    protected static string $resource = InsuranceOfferResource::class;
    protected static ?string $title   = 'Створити страхову пропозицію';

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
