<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use App\Models\LeadRequest;
use App\Models\User;
use App\Notifications\NewClientAssignedNotification;
use App\Services\Assignments\ManagerAssignmentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    public ?int $leadRequestId = null;

    protected ?LeadRequest $leadRequest = null;

    public function getTitle(): string
    {
        return 'Новий клієнт';
    }

    protected function resolveLeadRequest(?User $user): ?LeadRequest
    {
        if (! $this->leadRequestId) {
            return null;
        }

        $leadRequest = LeadRequest::query()->find($this->leadRequestId);

        if (! $leadRequest) {
            return null;
        }

        if (! $leadRequest->isVisibleTo($user)) {
            abort(403);
        }

        return $leadRequest;
    }

    protected function handleAlreadyConvertedLeadRequest(LeadRequest $leadRequest, User $user): bool
    {
        if (! $leadRequest->hasExistingClient()) {
            return false;
        }

        $client = $leadRequest->resolveConvertedClient();

        $body = 'На основі цієї вхідної заявки клієнта вже створено. Повторна конвертація заблокована.';
        $redirectUrl = static::getResource()::getUrl('index');

        if ($client && $client->isVisibleTo($user)) {
            $body = 'На основі цієї вхідної заявки клієнта вже створено. Відкрито існуючу картку клієнта.';
            $redirectUrl = static::getResource()::getUrl('edit', ['record' => $client->getKey()]);
        }

        Notification::make()
            ->warning()
            ->title('Заявку вже конвертовано')
            ->body($body)
            ->persistent()
            ->send();

        $this->redirect($redirectUrl);

        return true;
    }

    protected function resolveAutoAssignedManagerId(User $user, ?LeadRequest $leadRequest = null): ?int
    {
        if ($user->isManager()) {
            return $user->id;
        }

        if ($leadRequest instanceof LeadRequest && filled($leadRequest->assigned_user_id)) {
            return (int) $leadRequest->assigned_user_id;
        }

        return app(ManagerAssignmentService::class)->resolveLeastBusyManagerId();
    }

    public function mount(): void
    {
        $this->leadRequestId = Request::integer('lead_request_id') ?: null;

        parent::mount();

        /** @var User|null $user */
        $user = Auth::user();

        $leadRequest = $this->resolveLeadRequest($user);

        if (! $leadRequest) {
            return;
        }

        if ($user instanceof User && $this->handleAlreadyConvertedLeadRequest($leadRequest, $user)) {
            return;
        }

        $this->leadRequest = $leadRequest;

        $this->form->fill([
            'type' => $leadRequest->type,
            'status' => 'lead',
            'first_name' => $leadRequest->first_name,
            'last_name' => $leadRequest->last_name,
            'middle_name' => $leadRequest->middle_name,
            'company_name' => $leadRequest->company_name,
            'primary_email' => $leadRequest->email,
            'primary_phone' => $leadRequest->phone,
            'source' => $leadRequest->source,
            'assigned_user_id' => $leadRequest->assigned_user_id,
            'notes' => $leadRequest->comment,
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

    protected function ensureValidAssignedManager(array $data, User $user): array
    {
        $leadRequest = $this->resolveLeadRequest($user);

        if ($user->isManager()) {
            $data['assigned_user_id'] = $user->id;

            return $data;
        }

        $assignedUserId = isset($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : 0;

        if ($assignedUserId <= 0) {
            $assignedUserId = (int) ($this->resolveAutoAssignedManagerId($user, $leadRequest) ?? 0);
            $data['assigned_user_id'] = $assignedUserId;
        }

        $isValidManager = $assignedUserId > 0
            && User::query()
                ->whereKey($assignedUserId)
                ->where('role', 'manager')
                ->where('is_active', true)
                ->exists();

        if (! $isValidManager) {
            throw ValidationException::withMessages([
                'assigned_user_id' => 'Можна призначити лише активного менеджера.',
            ]);
        }

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user?->can('create', Client::class), 403);
        abort_unless($user instanceof User, 403);

        $data = $this->ensureValidAssignedManager($data, $user);

        if (blank($data['status'] ?? null)) {
            $data['status'] = 'lead';
        }

        $leadRequest = $this->resolveLeadRequest($user);

        if ($leadRequest && $leadRequest->hasExistingClient()) {
            throw ValidationException::withMessages([
                'first_name' => 'На основі цієї вхідної заявки клієнта вже створено. Повторна конвертація неможлива.',
            ]);
        }

        $duplicates = Client::query()
            ->withTrashed()
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

                if (filled($data['tax_id'] ?? null)) {
                    $query->orWhere('tax_id', $data['tax_id']);
                }
            })
            ->limit(5)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $body = $duplicates
                ->map(function (Client $client): string {
                    $managerName = $client->assignedUser?->name;
                    $archivedMark = $client->trashed() ? ' [архівний]' : '';

                    return '• ' . $client->display_label . $archivedMark . ($managerName ? " (менеджер: {$managerName})" : '');
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

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return DB::transaction(function () use ($data, $user): Model {
            if (! $this->leadRequestId) {
                return static::getModel()::create($data);
            }

            /** @var LeadRequest|null $leadRequest */
            $leadRequest = LeadRequest::query()
                ->lockForUpdate()
                ->find($this->leadRequestId);

            if (! $leadRequest) {
                return static::getModel()::create($data);
            }

            if (! $leadRequest->isVisibleTo($user)) {
                abort(403);
            }

            if ($leadRequest->hasExistingClient()) {
                throw ValidationException::withMessages([
                    'first_name' => 'На основі цієї вхідної заявки клієнта вже створено. Повторна конвертація неможлива.',
                ]);
            }

            /** @var Client $client */
            $client = static::getModel()::create($data);

            $leadRequest->update([
                'status' => 'converted',
                'converted_client_id' => $client->id,
            ]);

            $this->leadRequest = $leadRequest->fresh();

            return $client;
        });
    }

    protected function afterCreate(): void
    {
        /** @var Client|null $client */
        $client = $this->record;

        if (! $client instanceof Client) {
            return;
        }

        $assignedManager = User::query()->find($client->assigned_user_id);

        /** @var User|null $currentUser */
        $currentUser = Auth::user();

        if ($assignedManager instanceof User) {
            if (! ($currentUser instanceof User && (int) $currentUser->id === (int) $assignedManager->id)) {
                $assignedManager->notify(new NewClientAssignedNotification($client));
            }
        }

        if (! $this->leadRequestId) {
            return;
        }

        Notification::make()
            ->success()
            ->title('Заявку конвертовано')
            ->body('На основі вхідної заявки створено клієнта.')
            ->send();
    }
}