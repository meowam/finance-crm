<?php
namespace App\Filament\Resources\PolicyPayments\Tables;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Policies\PolicyResource;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PolicyPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('transaction_reference')
                    ->label('Транзакція')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Скопіювати номер транзакції'),

                TextColumn::make('policy.policy_number')
                    ->label('Номер полісу')
                    ->searchable()
                    ->url(fn($record) => $record->policy ?
                        PolicyResource::getUrl('edit', ['record' => $record->policy]) : null)
                    ->openUrlInNewTab()
                    ->tooltip('Відкрити поліс у новій вкладці')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match (mb_strtolower($state)) {
                        'paid'      => 'Сплачено',
                        'scheduled' => 'Очікує',
                        'overdue'   => 'Прострочено',
                        'canceled'  => 'Скасовано',
                        default     => $state,
                    })
                    ->color(fn(string $state) => match (mb_strtolower($state)) {
                        'paid'      => 'success',
                        'scheduled' => 'warning',
                        'overdue'   => 'danger',
                        'canceled'  => 'danger',
                        default     => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('policy.client.primary_email')
                    ->label('Email клієнта')
                    ->searchable()
                    ->url(fn($record) => $record->policy?->client
                            ? ClientResource::getUrl('edit', ['record' => $record->policy->client])
                            : null)
                    ->openUrlInNewTab()
                    ->tooltip('Відкрити клієнта у новій вкладці')
                    ->toggleable(),

                TextColumn::make('amount')
                    ->label('Сума')
                    ->alignRight()
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, ',', ' ') . ' ₴'),

                TextColumn::make('method')
                    ->label('Метод')
                    ->badge()
                    ->color(fn(?string $state) => match (mb_strtolower((string) $state)) {
                        'card', 'банківська карта', 'карта' => 'info',
                        'transfer', 'переказ' => 'success',
                        'cash', 'готівка'          => 'warning',
                        'paypal' => 'info',
                        'apple_pay', 'google_pay'  => 'success',
                        default  => 'secondary',
                    })
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('paid_at')
                    ->label('Сплачено о')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Строк оплати')
                    ->date('d.m.Y')
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
                EditAction::make()->label('Змінити'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->label('Видалити'),
            ]);
    }
}
