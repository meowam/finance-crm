<?php

namespace App\Filament\Pages;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class RequestPasswordReset extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Запит на зміну пароля';

    protected static ?string $title = 'Запит на зміну пароля';

    protected static string|\UnitEnum|null $navigationGroup = 'Обліковий запис';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.request-password-reset';

    public bool $hasPendingRequest = false;

    public function mount(): void
    {
        $this->refreshState();
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User
            && $user->is_active
            && ! $user->isAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function createRequest(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User || $user->isAdmin()) {
            abort(403);
        }

        $exists = PasswordResetRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            Notification::make()
                ->warning()
                ->title('Запит уже створено')
                ->body('Адміністратор ще не обробив попередній запит.')
                ->send();

            $this->refreshState();

            return;
        }

        PasswordResetRequest::create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        Notification::make()
            ->success()
            ->title('Запит створено')
            ->body('Адміністратор побачить його у системній панелі.')
            ->send();

        $this->refreshState();
    }

    protected function refreshState(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        $this->hasPendingRequest = $user instanceof User
            && PasswordResetRequest::query()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->exists();
    }
}