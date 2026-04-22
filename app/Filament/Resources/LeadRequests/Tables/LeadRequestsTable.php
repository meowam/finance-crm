<?php

namespace App\Filament\Resources\LeadRequests\Tables;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use App\Models\LeadRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LeadRequestsTable
{
    protected static function hasExistingClient(LeadRequest $record): bool
    {
        return $record->hasExistingClient();
    }

    protected static function resolveConvertedClient(LeadRequest $record): ?Client
    {
        return $record->resolveConvertedClient();
    }

    protected static function hasOpenableClient(LeadRequest $record): bool
    {
        return static::hasExistingClient($record);
    }

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
    ->visible(fn (LeadRequest $record) => ! static::hasExistingClient($record))
    ->url(function (LeadRequest $record): string {
        return ClientResource::getUrl('create', [
            'lead_request_id' => $record->id,
        ]);
    }),

                Action::make('openClient')
                    ->label('Відкрити клієнта')
                    ->icon('heroicon-o-user')
                    ->visible(fn (LeadRequest $record) => static::hasOpenableClient($record))
                    ->url(function (LeadRequest $record): string {
                        $client = static::resolveConvertedClient($record);

                        return ClientResource::getUrl('edit', ['record' => $client->getKey()]);
                    })
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