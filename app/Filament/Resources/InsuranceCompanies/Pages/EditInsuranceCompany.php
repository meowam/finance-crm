<?php

namespace App\Filament\Resources\InsuranceCompanies\Pages;

use App\Filament\Resources\InsuranceCompanies\InsuranceCompanyResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditInsuranceCompany extends EditRecord
{
    protected static string $resource = InsuranceCompanyResource::class;

    protected static ?string $title = 'Редагувати компанію страхування';

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
        $data['license_number'] = strtoupper((string) ($data['license_number'] ?? ''));

        return $data;
    }
}