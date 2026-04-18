<?php

namespace App\Filament\Resources\ClaimNotes\Pages;

use App\Filament\Resources\ClaimNotes\ClaimNoteResource;
use App\Models\Claim;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateClaimNote extends CreateRecord
{
    protected static string $resource = ClaimNoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        $data['user_id'] = $user?->id;

        if ($user instanceof User && $user->isManager()) {
            if (! empty($data['claim_id'])) {
                $claim = Claim::query()->find($data['claim_id']);

                if (! $claim || (int) $claim->reported_by_id !== (int) $user->id) {
                    abort(403);
                }
            }
        }

        return $data;
    }
}