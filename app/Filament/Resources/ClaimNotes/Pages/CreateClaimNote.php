<?php

namespace App\Filament\Resources\ClaimNotes\Pages;

use App\Filament\Resources\ClaimNotes\ClaimNoteResource;
use App\Models\Claim;
use App\Models\ClaimNote;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateClaimNote extends CreateRecord
{
    protected static string $resource = ClaimNoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->can('create', ClaimNote::class), 403);

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

        $data['user_id'] = $user->id;

        return $data;
    }
}