<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    public function getTitle(): string
    {
        return 'Новий клієнт';
    }

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user instanceof User && $user->isManager()) {
            $data['assigned_user_id'] = $user->id;
        }

        if (blank($data['status'] ?? null)) {
            $data['status'] = 'lead';
        }

        $duplicates = Client::query()
            ->where(function (Builder $query) use ($data) {
                if (filled($data['primary_email'] ?? null)) {
                    $query->orWhere('primary_email', $data['primary_email']);
                }

                if (filled($data['primary_phone'] ?? null)) {
                    $query->orWhere('primary_phone', $data['primary_phone']);
                }

                if (filled($data['document_number'] ?? null)) {
                    $query->orWhere('document_number', $data['document_number']);
                }
            })
            ->limit(3)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $body = $duplicates
                ->map(function (Client $client): string {
                    return '• ' . $client->display_label;
                })
                ->implode("\n");

            Notification::make()
                ->warning()
                ->title('Знайдено можливий дублікат клієнта')
                ->body("У системі вже є клієнт із таким email, телефоном або документом:\n{$body}")
                ->persistent()
                ->send();

            $this->halt();
        }

        return $data;
    }
}