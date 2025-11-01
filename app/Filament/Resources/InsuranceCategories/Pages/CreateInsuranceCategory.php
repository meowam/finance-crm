<?php

namespace App\Filament\Resources\InsuranceCategories\Pages;

use App\Filament\Resources\InsuranceCategories\InsuranceCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreateInsuranceCategory extends CreateRecord
{
    protected static string $resource = InsuranceCategoryResource::class;
    protected static ?string $title   = 'Створити страхування';

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
