<?php
namespace App\Filament\Resources\Claims\Pages;

use App\Filament\Resources\Claims\ClaimResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateClaim extends CreateRecord
{
    protected static string $resource = ClaimResource::class;
    protected static ?string $title   = 'Створити заяву';

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
