<?php

namespace App\Filament\Resources\Claims\Pages;

use App\Filament\Resources\Claims\ClaimResource;
use App\Models\Claim;
use App\Models\Policy;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateClaim extends CreateRecord
{
    protected static string $resource = ClaimResource::class;

    protected static ?string $title = 'Створити заяву';

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Створити');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Зберегти та створити наступний запис');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function hasCreateAnother(): bool
    {
        return true;
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Назад')
            ->url(static::getResource()::getUrl('index'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user?->can('create', Claim::class), 403);

        $data['reported_by_id'] = $user?->id;

        if (! empty($data['policy_id'])) {
            $policy = Policy::query()->find($data['policy_id']);

            if (! $policy || ! $policy->isVisibleTo($user)) {
                abort(403);
            }
        }

        return $data;
    }
}