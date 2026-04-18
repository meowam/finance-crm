<?php

namespace App\Filament\Resources\ClaimNotes\Tables;

use App\Filament\Resources\Claims\ClaimResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ClaimNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                /** @var User|null $user */
                $user = Auth::user();

                if ($user instanceof User && $user->isManager()) {
                    $query->whereHas('claim', function (Builder $claimQuery) use ($user) {
                        $claimQuery->where('reported_by_id', $user->id);
                    });
                }
            })
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->groups([
                Group::make('claim.claim_number')
                    ->label('Заява')
                    ->collapsible(),
            ])
            ->defaultGroup('claim.claim_number')
            ->groupingSettingsHidden()
            ->columns([
                TextColumn::make('claim.claim_number')
                    ->label('Номер заявки')
                    ->badge()
                    ->url(fn ($record) => ClaimResource::getUrl('edit', ['record' => $record->claim_id]))
                    ->openUrlInNewTab()
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Менеджер')
                    ->url(fn ($record) => UserResource::getUrl('edit', ['record' => $record->user_id]))
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable()
                    ->visible(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return ! ($user instanceof User && $user->isManager());
                    }),

                TextColumn::make('visibility')
                    ->label('Видимість')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'внутрішня' => 'gray',
                        'зовнішня'  => 'info',
                        default     => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('note')
                    ->label('Нотатка')
                    ->wrap()
                    ->limit(200)
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Редагувати'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Видалити')
                    ->visible(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $user instanceof User && ! $user->isManager();
                    }),
            ]);
    }
}