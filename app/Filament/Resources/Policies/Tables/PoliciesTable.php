<?php

namespace App\Filament\Resources\Policies\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PoliciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('policy_number')
                    ->label('Номер полісу')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.primary_email')
                    ->label('Email клієнта')
                    ->url(fn ($record) => url("/admin/clients/{$record->client_id}/edit"))
                    ->openUrlInNewTab()
                    ->searchable(),

                TextColumn::make('insuranceOffer.insuranceProduct.name')
                    ->label('Страховий продукт')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('agent.name')
                    ->label('Менеджер')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?: '—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(function (string $state) {
                        return match ($state) {
                            'draft'     => 'чернетка',
                            'active'    => 'активний',
                            'completed' => 'завершено',
                            'canceled'  => 'скасовано',
                            default     => $state,
                        };
                    })
                    ->color(function (string $state) {
                        return match ($state) {
                            'draft'     => 'warning',
                            'active'    => 'success',
                            'completed' => 'gray',
                            'canceled'  => 'danger',
                            default     => 'gray',
                        };
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('effective_date')
                    ->label('Початок дії')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('expiration_date')
                    ->label('Закінчення')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('premium_amount')
                    ->label('Cума до оплати')
                    ->money('UAH', locale: 'uk')
                    ->sortable(),

                TextColumn::make('coverage_amount')
                    ->label('Сума покриття')
                    ->money('UAH', locale: 'uk')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('payment_frequency')
                    ->label('Періодичність оплати')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'once' => 'разово',
                        'monthly' => 'щомісяця',
                        'quarterly' => 'щокварталу',
                        'yearly' => 'щороку',
                        default => $state,
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('commission_rate')
                    ->label('Комісія')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->suffix('%')
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
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Змінити'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Видалити вибране'),
            ]);
    }
}
