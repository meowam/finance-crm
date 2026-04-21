<?php

namespace App\Filament\Resources\Claims\Pages;

use App\Filament\Resources\Claims\ClaimResource;
use App\Models\Policy;
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

        if (! $user instanceof User) {
            abort(403);
        }

        abort_unless($user->can('update', $this->record), 403);
    }

    protected function normalizeNotesPayload(array $data, User $user): array
    {
        if (! isset($data['notes']) || ! is_array($data['notes'])) {
            return $data;
        }

        $existingNotes = $this->record->notes()
            ->get()
            ->keyBy('id');

        $normalized = [];

        foreach ($data['notes'] as $key => $noteData) {
            if (! is_array($noteData)) {
                continue;
            }

            $noteId = isset($noteData['id']) ? (int) $noteData['id'] : 0;

            if ($noteId > 0 && $existingNotes->has($noteId)) {
                $noteData['user_id'] = $existingNotes->get($noteId)->user_id;
            } else {
                unset($noteData['id']);
                $noteData['user_id'] = $user->id;
            }

            $normalized[$key] = $noteData;
        }

        $data['notes'] = $normalized;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->can('update', $this->record), 403);

        if (! empty($data['policy_id'])) {
            $policy = Policy::query()->find($data['policy_id']);

            if (! $policy || ! $policy->isVisibleTo($user)) {
                abort(403);
            }
        }

        $data['reported_by_id'] = (int) $this->record->reported_by_id;
        $data = $this->normalizeNotesPayload($data, $user);

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