<?php

namespace App\Filament\Resources\LeadRequests\Pages;

use App\Filament\Resources\LeadRequests\LeadRequestResource;
use App\Models\LeadRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateLeadRequest extends CreateRecord
{
    protected static string $resource = LeadRequestResource::class;

    protected static ?string $title = 'Створити вхідну заявку';

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Створити');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user?->can('create', LeadRequest::class), 403);

        if ($user instanceof User && $user->isManager()) {
            $data['assigned_user_id'] = $user->id;
        }

        if (blank($data['status'] ?? null)) {
            $data['status'] = 'new';
        }

        return $data;
    }
}