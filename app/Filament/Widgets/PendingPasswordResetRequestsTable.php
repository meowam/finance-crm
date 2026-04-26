<?php
namespace App\Filament\Widgets;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PendingPasswordResetRequestsTable extends BaseWidget
{
    protected static ?string $heading = 'Запити на зміну пароля';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && $user->isAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PasswordResetRequest::query()
                    ->with(['user', 'resolvedBy'])
                    ->where('status', 'pending')
                    ->latest()
            )
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Немає запитів на зміну пароля')
            ->emptyStateDescription('Усі запити вже оброблено або користувачі ще не створювали нових запитів.')
            ->emptyStateIcon('heroicon-o-key')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Користувач')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('user.role')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'admin'      => 'Адмін',
                        'supervisor' => 'Супервайзер',
                        'manager'    => 'Менеджер',
                        default      => $state ?: '—',
                    })
                    ->color(fn($state) => match ($state) {
                        'admin'      => 'danger',
                        'supervisor' => 'warning',
                        'manager'    => 'info',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pending'  => 'Очікує',
                        'resolved' => 'Виконано',
                        'rejected' => 'Відхилено',
                        default    => $state,
                    })
                    ->color(fn($state) => match ($state) {
                        'pending'  => 'warning',
                        'resolved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('openUser')
                    ->label('Відкрити користувача')
                    ->icon('heroicon-o-user')
                    ->url(fn(PasswordResetRequest $record) => $record->user
                            ? "/admin/users/{$record->user->id}"
                            : null
                    )
                    ->openUrlInNewTab(),

                Action::make('markResolved')
                    ->label('Позначити виконаним')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (PasswordResetRequest $record): void {
                        /** @var User|null $user */
                        $user = Auth::user();

                        if (! $user instanceof User || ! $user->isAdmin()) {
                            abort(403);
                        }

                        $record->update([
                            'status'         => 'resolved',
                            'resolved_by_id' => $user->id,
                            'resolved_at'    => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Запит закрито')
                            ->body('Запит на зміну пароля позначено як виконаний.')
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Відхилити')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (PasswordResetRequest $record): void {
                        /** @var User|null $user */
                        $user = Auth::user();

                        if (! $user instanceof User || ! $user->isAdmin()) {
                            abort(403);
                        }

                        $record->update([
                            'status'         => 'rejected',
                            'resolved_by_id' => $user->id,
                            'resolved_at'    => now(),
                        ]);

                        Notification::make()
                            ->warning()
                            ->title('Запит відхилено')
                            ->body('Запит на зміну пароля позначено як відхилений.')
                            ->send();
                    }),
            ]);
    }
}
