<?php
namespace App\Filament\Resources\Clients\Tables;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Реєстрація')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                TextColumn::make('pib')
                    ->label('ПІБ')
                    ->state(function ($record) {
                        $last  = trim((string) $record->last_name);
                        $first = trim((string) $record->first_name);
                        $mid   = trim((string) ($record->middle_name ?? ''));

                        if ($last === '' && $first === '') {
                            return '—';
                        }

                        if ($mid !== '') {
                            $fi = mb_strtoupper(mb_substr($first, 0, 1));
                            $mi = mb_strtoupper(mb_substr($mid, 0, 1));
                            return "{$last} {$fi}.{$mi}.";
                        }

                        // ❗ завжди Прізвище потім Ім’я
                        return trim("{$last} {$first}");
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where(function (Builder $q) use ($search) {
                            $q->where('last_name', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->orderBy('last_name', $direction)
                            ->orderBy('first_name', $direction);
                    }),

                TextColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'company'    => 'Компанія',
                        'individual' => 'Фізична особа',
                        default      => '—',
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'company'    => 'info',
                        'individual' => 'gray',
                        default      => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'active'   => 'Активний',
                        'archived' => 'Архівовано',
                        'lead'     => 'Потенційний',
                        default    => '—',
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'active'   => 'success',
                        'lead'     => 'warning',
                        'archived' => 'gray',
                        default    => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('primary_phone')
                    ->label('Телефон')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('primary_email')
                    ->label('Email')
                    ->url(fn($record) => "mailto:{$record->primary_email}")
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('document_number')
                    ->label('Документ')
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('tax_id')
                    ->label('ІПН / ЄДРПОУ')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('source')
                    ->label('Джерело')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'office'         => 'Офіс',
                        'online'         => 'Онлайн',
                        'recommendation' => 'Рекомендація',
                        null, '' => '—',
                        default          => (string) $state,
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'office'         => 'info',
                        'online'         => 'success',
                        'recommendation' => 'warning',
                        default          => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('assignedUser.name')
                    ->label('Менеджер')
                    ->placeholder('—')
                    ->url(fn($record) => $record->assigned_user_id
                            ? UserResource::getUrl('edit', ['record' => $record->assigned_user_id])
                            : null
                    )
                    ->openUrlInNewTab()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)

            ->filters([])
            ->recordActions([
                EditAction::make()->label('Редагувати'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->label('Видалити обране'),
            ]);
    }
}
