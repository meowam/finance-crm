<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use App\Models\LeadRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected ?LeadRequest $leadRequest = null;

    public function getTitle(): string
    {
        return 'Новий клієнт';
    }

    public function mount(): void
    {
        parent::mount();

        /** @var User|null $user */
        $user = Auth::user();

        $leadRequestId = Request::integer('lead_request_id');

        if (! $leadRequestId) {
            return;
        }

        $leadRequest = LeadRequest::query()->find($leadRequestId);

        if (! $leadRequest) {
            return;
        }

        if (! $leadRequest->isVisibleTo($user)) {
            abort(403);
        }

        $this->leadRequest = $leadRequest;

        $this->form->fill([
            'type' => Request::query('type', $leadRequest->type),
            'status' => 'lead',
            'first_name' => Request::query('first_name', $leadRequest->first_name),
            'last_name' => Request::query('last_name', $leadRequest->last_name),
            'middle_name' => Request::query('middle_name', $leadRequest->middle_name),
            'company_name' => Request::query('company_name', $leadRequest->company_name),
            'primary_email' => Request::query('primary_email', $leadRequest->email),
            'primary_phone' => Request::query('primary_phone', $leadRequest->phone),
            'source' => Request::query('source', $leadRequest->source),
            'assigned_user_id' => Request::query('assigned_user_id', $leadRequest->assigned_user_id),
            'notes' => Request::query('notes', $leadRequest->comment),
        ]);
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

        abort_unless($user?->can('create', Client::class), 403);

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
            ->limit(5)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $body = $duplicates
                ->map(function (Client $client): string {
                    $managerName = $client->assignedUser?->name;

                    return '• ' . $client->display_label . ($managerName ? " (менеджер: {$managerName})" : '');
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

    protected function afterCreate(): void
    {
        $leadRequestId = Request::integer('lead_request_id');

        if (! $leadRequestId) {
            return;
        }

        /** @var LeadRequest|null $leadRequest */
        $leadRequest = LeadRequest::query()->find($leadRequestId);

        if (! $leadRequest) {
            return;
        }

        $leadRequest->update([
            'status' => 'converted',
            'converted_client_id' => $this->record->id,
        ]);

        Notification::make()
            ->success()
            ->title('Заявку конвертовано')
            ->body('На основі вхідної заявки створено клієнта.')
            ->send();
    }
}