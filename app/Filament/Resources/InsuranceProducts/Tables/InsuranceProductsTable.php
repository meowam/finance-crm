<?php
namespace App\Filament\Resources\InsuranceProducts\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class InsuranceProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->groups([
                Group::make('category.name')
                    ->label('Категорія')
                    ->collapsible(),
            ])
            ->defaultGroup('category.name')
            ->groupingSettingsHidden()
            ->columns([
                TextColumn::make('category.code')
                    ->label('Код категорії')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Код продукту')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Опис')
                    ->wrap()
                    ->limit(120)
                    ->searchable(),

                IconColumn::make('sales_enabled')
                    ->label('Діє')
                    ->boolean()
                    ->sortable(),

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
