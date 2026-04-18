<?php

namespace App\Filament\Resources\InsuranceCategories\Pages;

use App\Filament\Resources\InsuranceCategories\InsuranceCategoryResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditInsuranceCategory extends EditRecord
{
    protected static string $resource = InsuranceCategoryResource::class;

    protected static ?string $title = 'Редагувати страхування';

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
}