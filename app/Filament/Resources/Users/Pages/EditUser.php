<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'Користувач';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        abort_unless($authUser instanceof User, 403);

        if ($authUser->isSupervisor()) {
            unset($data['role']);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}