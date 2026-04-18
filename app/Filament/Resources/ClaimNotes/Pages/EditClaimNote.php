<?php

namespace App\Filament\Resources\ClaimNotes\Pages;

use App\Filament\Resources\ClaimNotes\ClaimNoteResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditClaimNote extends EditRecord
{
    protected static string $resource = ClaimNoteResource::class;

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        /** @var User|null $user */
        $user = Auth::user();

        if (
            $user instanceof User &&
            $user->isManager() &&
            (int) optional($this->record->claim)->reported_by_id !== (int) $user->id
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