<?php
namespace App\Filament\Resources\InsuranceOffers\Pages;

use App\Filament\Resources\InsuranceOffers\Concerns\MutatesOfferData;
use App\Filament\Resources\InsuranceOffers\InsuranceOfferResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

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
    use MutatesOfferData;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->normalizeOfferData($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return static::getModel()::create($data);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                Notification::make()
                    ->danger()
                    ->title('Упс, така пропозиція вже існує')
                    ->body('Для цієї компанії, продукту, назви та тривалості запис уже є. Змініть параметри та повторіть.')
                    ->send();

                $this->halt();
            }

            throw $e;
        }
    }
}
