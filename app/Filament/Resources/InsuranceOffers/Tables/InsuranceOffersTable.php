<?php

namespace App\Filament\Resources\InsuranceOffers\Tables;
use App\Filament\Resources\InsuranceCompanies\InsuranceCompanyResource;
use App\Filament\Resources\InsuranceProducts\InsuranceProductResource;
use App\Models\InsuranceCompany;
use App\Models\InsuranceOffer;
use App\Models\InsuranceProduct;
use App\Models\User;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;


use Filament\Schemas\Components\Grid;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class InsuranceOffersTable
{
    protected static function canManageReferenceDirectory(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->isAdmin() || $user->isSupervisor());
    }

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
                    ->url(fn ($record) => self::canManageReferenceDirectory() && $record->insurance_product_id
                        ? InsuranceProductResource::getUrl('edit', ['record' => $record->insurance_product_id])
                        : null)
                    ->openUrlInNewTab(),

                TextColumn::make('insuranceCompany.name')
                    ->label('Страхова компанія')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => self::canManageReferenceDirectory() && $record->insurance_company_id
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
                    ->formatStateUsing(fn ($state) => is_null($state) ? '—' : number_format((float) $state, 2, ',', ' '))
                    ->suffix(' ₴'),

                TextColumn::make('coverage_amount')
                    ->label('Сума покриття')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => is_null($state) ? '—' : number_format((float) $state, 2, ',', ' '))
                    ->suffix(' ₴'),

                TextColumn::make('franchise')
                    ->label('Франшиза')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => is_null($state) ? '—' : number_format((float) $state, 2, ',', ' '))
                    ->suffix(' ₴')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('duration_months')
                    ->label('Тривалість')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? ($state . ' міс.') : '—')
                    ->color(fn ($state) => match ((int) $state) {
                        1 => 'gray',
                        3 => 'info',
                        6 => 'warning',
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
            ->filters([
                SelectFilter::make('insurance_product_id')
                    ->label('Продукт')
                    ->options(fn (): array => InsuranceProduct::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('insurance_company_id')
                    ->label('Страхова компанія')
                    ->options(fn (): array => InsuranceCompany::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('offer_name')
                    ->label('Назва пропозиції')
                    ->options(fn (): array => InsuranceOffer::query()
                        ->whereNotNull('offer_name')
                        ->where('offer_name', '!=', '')
                        ->distinct()
                        ->orderBy('offer_name')
                        ->pluck('offer_name', 'offer_name')
                        ->toArray())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('duration_months')
                    ->label('Тривалість')
                    ->options(fn (): array => InsuranceOffer::query()
                        ->whereNotNull('duration_months')
                        ->distinct()
                        ->orderBy('duration_months')
                        ->pluck('duration_months', 'duration_months')
                        ->mapWithKeys(fn ($value, $key): array => [
                            (string) $key => $value . ' міс.',
                        ])
                        ->toArray()),

                Filter::make('price_range')
                    ->label('Ціна, ₴')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('from')
                                    ->label('Від, ₴')
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('Напр. 1000'),

                                TextInput::make('until')
                                    ->label('До, ₴')
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('Напр. 3000'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $q): Builder => $q->where('price', '>=', (float) $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $q): Builder => $q->where('price', '<=', (float) $data['until']),
                            );
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Змінити')
                    ->visible(fn (): bool => self::canManageReferenceDirectory()),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Видалити вибрані')
                    ->visible(fn (): bool => self::canManageReferenceDirectory()),
            ]);
    }
}