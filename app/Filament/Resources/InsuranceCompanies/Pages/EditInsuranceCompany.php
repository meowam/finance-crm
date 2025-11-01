<?php
namespace App\Filament\Resources\InsuranceCompanies\Pages;

use App\Filament\Resources\InsuranceCompanies\InsuranceCompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInsuranceCompany extends EditRecord
{
    protected static string $resource = InsuranceCompanyResource::class;
    protected static ?string $title   = 'Редагувати компанію страхування';
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['license_number'] = strtoupper((string) ($data['license_number'] ?? ''));
        return $data;
    }
}
