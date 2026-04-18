<?php

namespace App\Filament\Resources\Claims\Pages;

use App\Filament\Resources\Claims\ClaimResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditClaim extends EditRecord
{
    protected static string $resource = ClaimResource::class;

    protected static ?string $title = 'Редагувати заяву';

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        /** @var User|null $user */
        $user = Auth::user();

        if (
            $user instanceof User &&
            $user->isManager() &&
            (int) $this->record->reported_by_id !== (int) $user->id
        ) {
            abort(403);
        }
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