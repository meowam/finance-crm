<?php
namespace App\Filament\Resources\InsuranceCompanies\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InsuranceCompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('name')
                    ->label('Назва компанії')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('license_number')
                    ->label('Номер ліцензії')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('country')
                    ->label('Країна')
                    ->searchable()
                    ->sortable()->toggleable(),

                TextColumn::make('contact_email')
                    ->label('Ел. пошта')
                    ->url(fn($record) => $record->contact_email ? 'mailto:' . $record->contact_email : null)
                    ->openUrlInNewTab(false)
                    ->searchable(),

                TextColumn::make('contact_phone')
                    ->label('Телефон')
                    ->url(fn($record) => $record->contact_phone ? 'tel:' . preg_replace('/\s+/', '', $record->contact_phone) : null)
                    ->openUrlInNewTab(false)
                    ->searchable()->toggleable(),

                TextColumn::make('website')
                    ->label('Вебсайт')
                    ->formatStateUsing(fn($state) => $state ? preg_replace('#^https?://#i', '', $state) : null)
                    ->url(fn($record) => $record->website
                            ? (preg_match('#^https?://#i', $record->website) ? $record->website : 'https://' . $record->website)
                            : null)
                    ->openUrlInNewTab()
                    ->searchable()->toggleable(),

                TextColumn::make('logo_path')
                    ->label('Логотип') 
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()->label('Змінити'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->label('Видалити вибрані'),
            ]);
    }
}
