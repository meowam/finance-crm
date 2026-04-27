<?php

namespace App\Filament\Resources\ClaimNotes\Pages;

use App\Filament\Resources\ClaimNotes\ClaimNoteResource;
use App\Models\Claim;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditClaimNote extends EditRecord
{
    protected static string $resource = ClaimNoteResource::class;

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->can('update', $this->record), 403);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->can('update', $this->record), 403);

        $claimId = isset($data['claim_id']) ? (int) $data['claim_id'] : 0;

        if ($claimId <= 0) {
            throw ValidationException::withMessages([
                'claim_id' => 'Оберіть заяву.',
            ]);
        }

        $claim = Claim::query()
            ->with('policy')
            ->find($claimId);

        if (! $claim || ! $claim->isVisibleTo($user)) {
            abort(403);
        }

        // Автор нотатки не змінюється при редагуванні.
        $data['user_id'] = (int) $this->record->user_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        return [
            DeleteAction::make()
                ->visible($user instanceof User && $user->can('delete', $this->record)),
        ];
    }
}