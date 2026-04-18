<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_if($user->isManager(), 403);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        abort_unless($authUser instanceof User, 403);

        if ($authUser->isSupervisor()) {
            $data['role'] = 'manager';
        }

        return $data;
    }
}