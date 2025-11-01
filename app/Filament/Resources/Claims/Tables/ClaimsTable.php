<?php
namespace App\Filament\Resources\Claims\Tables;

use App\Filament\Resources\Policies\PolicyResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Claim;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClaimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('reported_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->columns([TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'на розгляді' => 'warning',
                        'виплачено'   => 'success',
                        'схвалено'    => 'info',
                        'відхилено'   => 'danger',
                        default       => 'gray',
                    })
                    ->sortable(query: fn(Builder $query, string $direction) =>
                        $query->orderByRaw("CASE status WHEN 'на розгляді' THEN 1 WHEN 'схвалено' THEN 2 WHEN 'відхилено' THEN 3 WHEN 'виплачено' THEN 4 ELSE 5 END $direction"))
                    ->searchable(),

                TextColumn::make('claim_number')
                    ->label('Номер заяви')
                    ->searchable(),

                TextColumn::make('policy.policy_number')
                    ->label('Поліс')
                    ->url(fn(Claim $record) => PolicyResource::getUrl('edit', ['record' => $record->policy_id]))
                    ->openUrlInNewTab()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reported_at')
                    ->label('Дата звернення')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('loss_occurred_at')
                    ->label('Дата події')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('loss_location')
                    ->label('Місце події')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cause')
                    ->label('Причина')
                    ->searchable(),

                TextColumn::make('reportedBy.name')
                    ->label('Менеджер')
                    ->url(fn(Claim $record) => UserResource::getUrl('edit', ['record' => $record->reported_by_id]))
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount_claimed')
                    ->label('Заявлена сума')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('amount_reserve')
                    ->label('Резервна сума')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('amount_paid')
                    ->label('Виплачено')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('notes_count')
                    ->label('Нотаток')
                    ->counts('notes')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()->label('Редагувати'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->label('Видалити'),
            ]);
    }
}
