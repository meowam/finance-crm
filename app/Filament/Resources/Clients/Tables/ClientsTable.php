<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Filament\Resources\Policies\PolicyResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                /** @var User|null $user */
                $user = Auth::user();

                if ($user instanceof User) {
                    $query->visibleTo($user);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Реєстрація')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                TextColumn::make('pib')
                    ->label('Клієнт / контакт')
                    ->state(function ($record) {
                        $last  = trim((string) $record->last_name);
                        $first = trim((string) $record->first_name);
                        $mid   = trim((string) ($record->middle_name ?? ''));

                        $fullName = trim(implode(' ', array_filter([$last, $first, $mid])));

                        if ($record->type === 'company' && filled($record->company_name)) {
                            return $fullName !== ''
                                ? "{$record->company_name} ({$fullName})"
                                : (string) $record->company_name;
                        }

                        return $fullName !== '' ? $fullName : '—';
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where(function (Builder $q) use ($search) {
                            $q->where('last_name', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%")
                                ->orWhere('primary_phone', 'like', "%{$search}%")
                                ->orWhere('primary_email', 'like', "%{$search}%")
                                ->orWhere('document_number', 'like', "%{$search}%")
                                ->orWhere('tax_id', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->orderBy('last_name', $direction)
                            ->orderBy('first_name', $direction);
                    }),

                TextColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'company'    => 'Компанія',
                        'individual' => 'Фізична особа',
                        default      => '—',
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'company'    => 'info',
                        'individual' => 'gray',
                        default      => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active'   => 'Активний',
                        'archived' => 'Архівовано',
                        'lead'     => 'Потенційний',
                        default    => '—',
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'   => 'success',
                        'lead'     => 'warning',
                        'archived' => 'gray',
                        default    => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('primary_phone')
                    ->label('Телефон')
                    ->copyable()
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('primary_email')
                    ->label('Email')
                    ->url(fn ($record) => "mailto:{$record->primary_email}")
                    ->copyable()
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('document_number')
                    ->label('Документ')
                    ->copyable()
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('tax_id')
                    ->label('ІПН / ЄДРПОУ')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('source')
                    ->label('Джерело')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'office'         => 'Офіс',
                        'online'         => 'Онлайн',
                        'recommendation' => 'Рекомендація',
                        'landing'        => 'Лендінг',
                        'other'          => 'Інше',
                        null, ''         => '—',
                        default          => (string) $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'office'         => 'info',
                        'online'         => 'success',
                        'recommendation' => 'warning',
                        'landing'        => 'primary',
                        'other'          => 'gray',
                        default          => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('assignedUser.name')
                    ->label('Менеджер')
                    ->placeholder('—')
                    ->url(fn ($record) => $record->assigned_user_id
                        ? UserResource::getUrl('edit', ['record' => $record->assigned_user_id])
                        : null)
                    ->openUrlInNewTab()
                    ->sortable()
                    ->toggleable()
                    ->visible(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return ! ($user instanceof User && $user->isManager());
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'lead'     => 'Потенційний',
                        'active'   => 'Активний',
                        'archived' => 'Архівовано',
                    ]),

                SelectFilter::make('source')
                    ->label('Джерело')
                    ->options([
                        'office'         => 'Офіс',
                        'online'         => 'Онлайн',
                        'recommendation' => 'Рекомендація',
                        'landing'        => 'Лендінг',
                        'other'          => 'Інше',
                    ]),

                SelectFilter::make('assigned_user_id')
                    ->label('Менеджер')
                    ->options(function (): array {
                        /** @var User|null $user */
                        $user = Auth::user();

                        if ($user instanceof User && $user->isManager()) {
                            return [];
                        }

                        return User::query()
                            ->where('role', 'manager')
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->visible(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return ! ($user instanceof User && $user->isManager());
                    }),

                Filter::make('created_from')
                    ->label('Дата створення')
                    ->schema([
                        DatePicker::make('from')->label('Від'),
                        DatePicker::make('until')->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Редагувати'),

                Action::make('policies')
                    ->label('Усі поліси клієнта')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record): string => PolicyResource::getUrl('index', [
                        'tableSearch' => (string) $record->primary_email,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Видалити обране')
                    ->visible(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $user instanceof User && $user->can('deleteAny', \App\Models\Client::class);
                    }),
            ]);
    }
}