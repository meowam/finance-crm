<?php
namespace App\Filament\Resources\InsuranceCategories\Pages;

use App\Filament\Resources\InsuranceCategories\InsuranceCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsuranceCategories extends ListRecords
{
    protected static string $resource = InsuranceCategoryResource::class;
    protected static ?string $title   = 'Страхування';
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
