<?php
namespace App\Filament\Resources\InsuranceCompanies\Pages;

use App\Filament\Resources\InsuranceCompanies\InsuranceCompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsuranceCompanies extends ListRecords
{
    protected static string $resource = InsuranceCompanyResource::class;
    protected static ?string $title   = 'Компанія страхування';
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
