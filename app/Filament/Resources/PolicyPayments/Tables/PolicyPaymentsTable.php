<?php
namespace App\Filament\Resources\PolicyPayments\Tables;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Policies\PolicyResource;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PolicyPaymentsTable
{
    protected static function str($v): string
    {return $v instanceof BackedEnum ? (string) $v->value : (string) $v;}
    protected static function low($v): string
    {return mb_strtolower(self::str($v));}

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn($state) => match (self::low($state)) {
                        'paid'      => 'Сплачено',
                        'scheduled' => 'Очікує',
                        'overdue'   => 'Прострочено',
                        'canceled'  => 'Скасовано',
                        'draft'     => 'Чернетка',
                        default     => self::str($state),
                    })
                    ->color(fn($state) => match (self::low($state)) {
                        'paid'      => 'success',
                        'scheduled' => 'warning',
                        'overdue'   => 'danger',
                        'canceled'  => 'danger',
                        'draft'     => 'gray',
                        default     => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('transaction_reference')
                    ->label('Транзакція')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('policy.policy_number')
                    ->label('Номер полісу')
                    ->searchable()
                    ->url(fn($record) => $record->policy ? PolicyResource::getUrl('edit', ['record' => $record->policy]) : null)
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('method')
                    ->label('Метод')
                    ->badge()
                    ->formatStateUsing(fn($state) => match (self::low($state)) {
                        'card'      => 'Картка',
                        'transfer'  => 'Переказ',
                        'cash'      => 'Готівка',
                        'no_method' => 'Не вибрано',
                        default     => self::str($state),
                    })
                    ->color(fn($state) => match (self::low($state)) {
                        'card'      => 'info',
                        'transfer'  => 'success',
                        'cash'      => 'warning',
                        'no_method' => 'gray',
                        default     => 'gray',
                    })
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('policy.client.primary_email')
                    ->label('Email клієнта')
                    ->searchable()
                    ->url(fn($record) => $record->policy?->client ? ClientResource::getUrl('edit', ['record' => $record->policy->client]) : null)
                    ->openUrlInNewTab()
                    ->toggleable(),

                TextColumn::make('amount')
                    ->label('Сума')
                    ->alignRight()
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, ',', ' ') . ' ₴'),

                TextColumn::make('payment_info')
                    ->label('Оплата')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $status = $record->status instanceof \BackedEnum  ? $record->status->value : (string) $record->status;
                        $status = mb_strtolower(trim($status));

                        $paidRaw = $record->getRawOriginal('paid_at');
                        $initRaw = $record->getRawOriginal('initiated_at');

                        $paid = $paidRaw ? \Illuminate\Support\Carbon::parse($paidRaw)->format('d.m.Y H:i') : null;
                        $init = $initRaw ? \Illuminate\Support\Carbon::parse($initRaw)->format('d.m.Y H:i') : null;

                        if ($status === 'canceled') {
                            return 'Оплата скасована';
                        }

                        if ($paid) {
                            return 'Сплачено: ' . $paid;
                        }

                        if ($status === 'scheduled' && $init) {
                            return 'Ініційовано: ' . $init;
                        }

                        if ($status === 'overdue') {
                            return 'Прострочено';
                        }

                        if ($status === 'draft' || (! $paid && ! $init)) {
                            return 'Не сплачено';
                        }

                        return 'Не сплачено';
                    })
                    ->color(function ($state, $record) {
                        $status  = $record->status instanceof \BackedEnum  ? $record->status->value : (string) $record->status;
                        $status  = mb_strtolower(trim($status));
                        $hasPaid = (bool) $record->getRawOriginal('paid_at');
                        $hasInit = (bool) $record->getRawOriginal('initiated_at');

                        if ($status === 'canceled') {
                            return 'danger';
                        }

                        if ($hasPaid) {
                            return 'success';
                        }
                        // если есть и paid, и initiated — всё равно зелёный paid
                        if ($status === 'scheduled' && $hasInit) {
                            return 'warning';
                        }

                        if ($status === 'overdue') {
                            return 'danger';
                        }

                        if ($status === 'draft' || (! $hasPaid && ! $hasInit)) {
                            return 'gray';
                        }

                        return 'gray';
                    }),

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
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft'     => 'Чернетка',
                        'scheduled' => 'Очікує',
                        'paid'      => 'Сплачено',
                        'overdue'   => 'Прострочено',
                        'canceled'  => 'Скасовано',
                    ]),
                SelectFilter::make('method')
                    ->label('Метод')
                    ->options([
                        'cash'      => 'Готівка',
                        'card'      => 'Картка',
                        'transfer'  => 'Переказ',
                        'no_method' => 'Не вибрано',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Змінити')
                    ->visible(fn($record) => ! in_array(self::low($record->status), ['paid', 'overdue'], true)),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Видалити')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $records->filter(fn($r) => in_array(mb_strtolower((string) ($r->status instanceof BackedEnum ? $r->status->value : $r->status)), ['draft', 'canceled'], true))
                            ->each->delete();
                    }),
            ]);
    }
}
