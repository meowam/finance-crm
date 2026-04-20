<?php

namespace App\Filament\Resources\LeadRequests\Tables;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use App\Models\LeadRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LeadRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('display_label')
                    ->label('Лід')
                    ->state(fn (LeadRequest $record) => $record->display_label)
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('interest')
                    ->label('Інтерес')
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('source')
                    ->label('Джерело')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'office' => 'Офіс',
                        'online' => 'Онлайн',
                        'recommendation' => 'Рекомендація',
                        'landing' => 'Лендінг',
                        'other' => 'Інше',
                        default => (string) $state,
                    }),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'new' => 'Нова',
                        'in_progress' => 'В роботі',
                        'converted' => 'Конвертовано',
                        'rejected' => 'Відхилено',
                        default => (string) $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'new' => 'warning',
                        'in_progress' => 'info',
                        'converted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('assignedUser.name')
                    ->label('Менеджер')
                    ->visible(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return ! ($user instanceof User && $user->isManager());
                    }),

                TextColumn::make('convertedClient.display_label')
                    ->label('Клієнт')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Нова',
                        'in_progress' => 'В роботі',
                        'converted' => 'Конвертовано',
                        'rejected' => 'Відхилено',
                    ]),

                SelectFilter::make('source')
                    ->label('Джерело')
                    ->options([
                        'office' => 'Офіс',
                        'online' => 'Онлайн',
                        'recommendation' => 'Рекомендація',
                        'landing' => 'Лендінг',
                        'other' => 'Інше',
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label('Редагувати'),

                Action::make('convertToClient')
                    ->label('Створити клієнта')
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn (LeadRequest $record) => $record->status !== 'converted')
                    ->requiresConfirmation()
                    ->action(function (LeadRequest $record) {
                        if ($record->converted_client_id) {
                            Notification::make()
                                ->warning()
                                ->title('Заявка вже конвертована')
                                ->send();

                            return;
                        }

                        $client = Client::create([
                            'type' => $record->type,
                            'status' => 'lead',
                            'first_name' => $record->first_name,
                            'last_name' => $record->last_name,
                            'middle_name' => $record->middle_name,
                            'company_name' => $record->company_name,
                            'primary_email' => $record->email,
                            'primary_phone' => $record->phone,
                            'source' => in_array($record->source, ['office', 'online', 'recommendation'], true)
                                ? $record->source
                                : 'online',
                            'assigned_user_id' => $record->assigned_user_id,
                            'notes' => $record->comment,
                        ]);

                        $record->update([
                            'status' => 'converted',
                            'converted_client_id' => $client->id,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Клієнта створено')
                            ->body('Заявку успішно конвертовано в клієнта.')
                            ->send();
                    }),

                Action::make('openClient')
                    ->label('Відкрити клієнта')
                    ->icon('heroicon-o-user')
                    ->visible(fn (LeadRequest $record) => filled($record->converted_client_id))
                    ->url(fn (LeadRequest $record) => ClientResource::getUrl('edit', ['record' => $record->converted_client_id]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Видалити вибране')
                    ->visible(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $user instanceof User && $user->can('deleteAny', LeadRequest::class);
                    }),
            ]);
    }
}