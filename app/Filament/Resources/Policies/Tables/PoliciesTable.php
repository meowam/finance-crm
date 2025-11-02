<?php
namespace App\Filament\Resources\Policies\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Models\Policy;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PoliciesTable
{
    protected static function str($v): string
    {return $v instanceof BackedEnum ? (string) $v->value : (string) $v;}
    protected static function low($v): string
    {return mb_strtolower(self::str($v));}

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query
                    ->with([
                        'client:id,primary_email',
                        'insuranceOffer.insuranceProduct:id,name',
                        'agent:id,name',
                    ])
                    ->addSelect([
                        'latest_payment_status' => DB::query()
                            ->from('policy_payments as lp')
                            ->select('lp.status')
                            ->whereColumn('lp.policy_id', 'policies.id')
                            ->orderByDesc('lp.id')
                            ->limit(1),
                    ]);
            })

            ->defaultPaginationPageOption(25)
            ->columns([

                TextColumn::make('policy_number')
                    ->label('Номер полісу')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('latest_payment_status')
                    ->label('Оплата')
                    ->badge()
                    ->formatStateUsing(fn($state) => match (self::low($state)) {
                        'paid'      => 'Сплачено',
                        'scheduled' => 'Очікує',
                        'overdue'   => 'Прострочено',
                        'canceled'  => 'Скасовано',
                        'draft'     => 'Чернетка',
                        ''          => '—',
                        default     => self::str($state),
                    })
                    ->color(fn($state) => match (self::low($state)) {
                        'paid'      => 'success',
                        'scheduled' => 'warning',
                        'overdue'   => 'danger',
                        'canceled'  => 'danger',
                        'draft'     => 'gray',
                        ''          => 'gray',
                        default     => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('client.primary_email')
                    ->label('Email клієнта')
                    ->url(fn($record) => url("/admin/clients/{$record->client_id}/edit"))
                    ->openUrlInNewTab()
                    ->searchable(),

                TextColumn::make('insuranceOffer.insuranceProduct.name')
                    ->label('Страховий продукт')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('agent.name')
                    ->label('Менеджер')
                    ->url(fn(Policy $record) => UserResource::getUrl('edit', ['record' => $record->agent_id]))
                    ->openUrlInNewTab()
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state ?: '—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn($state) => match (self::low($state)) {
                        'draft'     => 'чернетка',
                        'active'    => 'активний',
                        'completed' => 'завершено',
                        'canceled'  => 'скасовано',
                        default     => self::str($state),
                    })
                    ->color(fn($state) => match (self::low($state)) {
                        'draft'     => 'warning',
                        'active'    => 'success',
                        'completed' => 'gray',
                        'canceled'  => 'danger',
                        default     => 'gray',
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('payment_due_at')
                    ->label('Дедлайн оплати')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('effective_date')
                    ->label('Початок дії')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('expiration_date')
                    ->label('Закінчення дії')
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
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('payment_frequency')
                    ->label('Періодичність')
                    ->formatStateUsing(fn($state) => match (self::str($state)) {
                        'once'      => 'разово',
                        'monthly'   => 'щомісяця',
                        'quarterly' => 'щокварталу',
                        'yearly'    => 'щороку',
                        default     => self::str($state),
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('commission_rate')
                    ->label('Комісія')
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2))
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
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft'     => 'чернетка',
                        'active'    => 'активний',
                        'completed' => 'завершено',
                        'canceled'  => 'скасовано',
                    ]),
                SelectFilter::make('latest_payment_status')
                    ->label('Оплата')
                    ->options([
                        'draft'     => 'Чернетка',
                        'scheduled' => 'Очікує',
                        'paid'      => 'Сплачено',
                        'overdue'   => 'Прострочено',
                        'canceled'  => 'Скасовано',
                    ])
                    ->query(function ($query, $value) {
                        if (! $value) {
                            return $query;
                        }

                        $latestSub = DB::query()
                            ->from('policy_payments as t')
                            ->selectRaw('MAX(t.id)')
                            ->whereColumn('t.policy_id', 'policies.id');
                        return $query->whereIn('id', function (Builder $q) use ($value, $latestSub) {
                            $q->from('policies as p2')
                                ->select('p2.id')
                                ->whereExists(function (Builder $inner) use ($value, $latestSub) {
                                    $inner->from('policy_payments as lp2')
                                        ->select(DB::raw(1))
                                        ->whereColumn('lp2.policy_id', 'p2.id')
                                        ->where('lp2.id', '=', $latestSub)
                                        ->where('lp2.status', '=', $value);
                                });
                        });
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Змінити'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->label('Видалити вибране'),
            ]);
    }
}
