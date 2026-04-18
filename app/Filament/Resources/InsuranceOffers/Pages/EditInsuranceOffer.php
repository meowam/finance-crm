<?php

namespace App\Filament\Resources\InsuranceOffers\Pages;

use App\Filament\Resources\InsuranceOffers\Concerns\MutatesOfferData;
use App\Filament\Resources\InsuranceOffers\InsuranceOfferResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

class EditInsuranceOffer extends EditRecord
{
    use MutatesOfferData;

    protected static string $resource = InsuranceOfferResource::class;

    protected static ?string $title = 'Редагувати страхову пропозицію';

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_if($user->isManager(), 403);
    }

    protected function getHeaderActions(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        return [
            DeleteAction::make()
                ->visible($user instanceof User && ! $user->isManager()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->normalizeOfferData($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            $record->update($data);

            return $record;
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