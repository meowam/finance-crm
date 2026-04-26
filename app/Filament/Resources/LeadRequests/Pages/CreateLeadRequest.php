<?php

namespace App\Filament\Resources\LeadRequests\Pages;

use App\Filament\Resources\LeadRequests\LeadRequestResource;
use App\Models\LeadRequest;
use App\Models\User;
use App\Notifications\NewLeadAssignedNotification;
use App\Services\Assignments\ManagerAssignmentService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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

    protected function resolveAutoAssignedManagerId(User $user): ?int
    {
        if ($user->isManager()) {
            return $user->id;
        }

        return app(ManagerAssignmentService::class)->resolveLeastBusyManagerId();
    }

    protected function ensureValidAssignedManager(array $data, User $user): array
    {
        if ($user->isManager()) {
            $data['assigned_user_id'] = $user->id;

            return $data;
        }

        $assignedUserId = isset($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : 0;

        if ($assignedUserId <= 0) {
            $assignedUserId = (int) ($this->resolveAutoAssignedManagerId($user) ?? 0);
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

        abort_unless($user?->can('create', LeadRequest::class), 403);
        abort_unless($user instanceof User, 403);

        $data = $this->ensureValidAssignedManager($data, $user);

        if (blank($data['status'] ?? null)) {
            $data['status'] = 'new';
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var LeadRequest|null $leadRequest */
        $leadRequest = $this->record;

        if (! $leadRequest instanceof LeadRequest) {
            return;
        }

        $assignedManager = User::query()->find($leadRequest->assigned_user_id);

        /** @var User|null $currentUser */
        $currentUser = Auth::user();

        if (! $assignedManager instanceof User) {
            return;
        }

        if ($currentUser instanceof User && (int) $currentUser->id === (int) $assignedManager->id) {
            return;
        }

        $assignedManager->notify(new NewLeadAssignedNotification($leadRequest));
    }
}