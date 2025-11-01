<?php
namespace App\Filament\Resources\InsuranceOffers\Tables;

use App\Filament\Resources\InsuranceCompanies\InsuranceCompanyResource;
use App\Filament\Resources\InsuranceProducts\InsuranceProductResource;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InsuranceOffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('insuranceProduct.category.code')
                    ->label('Код категорії')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('insuranceProduct.code')
                    ->label('Код продукту')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('insuranceProduct.name')
                    ->label('Продукт')
                    ->searchable()
                    ->sortable()
                    ->url(fn($record) => $record->insurance_product_id
                        ? InsuranceProductResource::getUrl('edit', ['record' => $record->insurance_product_id])
                        : null)
                    ->openUrlInNewTab(),

                TextColumn::make('insuranceCompany.name')
                    ->label('Страхова компанія')
                    ->searchable()
                    ->sortable()
                    ->url(fn($record) => $record->insurance_company_id
                        ? InsuranceCompanyResource::getUrl('edit', ['record' => $record->insurance_company_id])
                        : null)
                    ->openUrlInNewTab(),

                TextColumn::make('offer_name')
                    ->label('Назва пропозиції')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Ціна')
                    ->sortable()
                    ->formatStateUsing(fn($state) => is_null($state) ? '—' : number_format((float)$state, 2, ',', ' '))
                    ->suffix(' ₴'),

                TextColumn::make('coverage_amount')
                    ->label('Сума покриття')
                    ->sortable()
                    ->formatStateUsing(fn($state) => is_null($state) ? '—' : number_format((float)$state, 2, ',', ' '))
                    ->suffix(' ₴'),

                TextColumn::make('franchise')
                    ->label('Франшиза')
                    ->sortable()
                    ->formatStateUsing(fn($state) => is_null($state) ? '—' : number_format((float)$state, 2, ',', ' '))
                    ->suffix(' ₴')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('duration_months')
                    ->label('Тривалість')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? ($state . ' міс.') : '—')
                    ->color(fn($state) => match ((int) $state) {
                        1  => 'gray',
                        3  => 'info',
                        6  => 'warning',
                        12 => 'success',
                        default => 'primary',
                    })
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
