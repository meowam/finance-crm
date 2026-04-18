<?php

namespace App\Filament\Resources\InsuranceCategories\Pages;

use App\Filament\Resources\InsuranceCategories\InsuranceCategoryResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListInsuranceCategories extends ListRecords
{
    protected static string $resource = InsuranceCategoryResource::class;

    protected static ?string $title = 'Страхування';

    protected function getHeaderActions(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        return [
            CreateAction::make()
                ->visible($user instanceof User && ! $user->isManager()),
        ];
    }
}