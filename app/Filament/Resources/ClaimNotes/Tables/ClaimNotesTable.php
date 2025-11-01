<?php
namespace App\Filament\Resources\ClaimNotes\Tables;

use App\Filament\Resources\Claims\ClaimResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class ClaimNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                    ->sortable(),

                TextColumn::make('visibility')
                    ->label('Видимість')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'внутрішня' => 'gray',
                        'зовнішня'  => 'info',
                        default     => 'gray',
                    })
                    ->url(fn ($record) => ClaimResource::getUrl('edit', ['record' => $record->claim_id]))
                    ->openUrlInNewTab()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('note')
                    ->label('Нотатка')
                    ->wrap()
                    ->limit(200)
                    ->url(fn ($record) => ClaimResource::getUrl('edit', ['record' => $record->claim_id]))
                    ->openUrlInNewTab()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->url(fn ($record) => ClaimResource::getUrl('edit', ['record' => $record->claim_id]))
                    ->openUrlInNewTab()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(false)
                    ->url(fn ($record) => ClaimResource::getUrl('edit', ['record' => $record->claim_id]))
                    ->icon('heroicon-m-pencil-square'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
