<?php

namespace App\Filament\Resources\Policies\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PoliciesTable
{
    protected static function str($v): string
    {
        return $v instanceof BackedEnum ? (string) $v->value : (string) $v;
    }

    protected static function low($v): string
    {
        return mb_strtolower(self::str($v));
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                /** @var User|null $user */
                $user = Auth::user();

                $query
                    ->visibleTo($user)
                    ->with([
                        'client:id,primary_email,first_name,last_name,middle_name,company_name,type',
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
                    ->formatStateUsing(fn ($state) => match (self::low($state)) {
                        'paid'      => 'Сплачено',
                        'scheduled' => 'Очікує',
                        'overdue'   => 'Прострочено',
                        'canceled'  => 'Скасовано',
                        'draft'     => 'Чернетка',
                        'refunded' => 'Повернено',
                        ''          => '—',
                        default     => self::str($state),
                    })
                    ->color(fn ($state) => match (self::low($state)) {
                        'paid'      => 'success',
                        'scheduled' => 'warning',
                        'overdue'   => 'danger',
                        'canceled'  => 'danger',
                        'draft'     => 'gray',
                        'refunded' => 'danger',
                        ''          => 'gray',
                        default     => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('client_summary')
                    ->label('Клієнт')
                    ->state(function ($record): string {
                        $client = $record->client;

                        if (! $client) {
                            return '—';
                        }

                        if ($client->type === 'company' && filled($client->company_name)) {
                            return (string) $client->company_name;
                        }

                        $parts = array_filter([
                            $client->last_name,
                            $client->first_name,
                            $client->middle_name,
                        ]);

                        return $parts !== [] ? implode(' ', $parts) : ($client->primary_email ?: '—');
                    })
                    ->searchable(query: function (EloquentBuilder $query, string $search) {
                        $query->whereHas('client', function (EloquentBuilder $clientQuery) use ($search) {
                            $clientQuery
                                ->where('primary_email', 'like', "%{$search}%")
                                ->orWhere('primary_phone', 'like', "%{$search}%")
                                ->orWhere('document_number', 'like', "%{$search}%")
                                ->orWhere('tax_id', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable(),

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
                    ->url(fn (Policy $record) => UserResource::getUrl('edit', ['record' => $record->agent_id]))
                    ->openUrlInNewTab()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?: '—')
                    ->toggleable()
                    ->visible(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return ! ($user instanceof User && $user->isManager());
                    }),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match (self::low($state)) {
                        'draft'     => 'чернетка',
                        'active'    => 'активний',
                        'completed' => 'завершено',
                        'canceled'  => 'скасовано',
                        default     => self::str($state),
                    })
                    ->color(fn ($state) => match (self::low($state)) {
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
                    ->formatStateUsing(fn ($state) => match (self::str($state)) {
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
                        'refunded' => 'Повернено',
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

                SelectFilter::make('client_id')
                    ->label('Клієнт')
                    ->options(function (): array {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return Client::query()
                            ->visibleTo($user)
                            ->orderBy('last_name')
                            ->orderBy('first_name')
                            ->limit(100)
                            ->get()
                            ->mapWithKeys(function (Client $client) {
                                return [$client->id => $client->display_label];
                            })
                            ->toArray();
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Змінити'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Видалити вибране')
                    ->visible(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $user instanceof User && $user->can('deleteAny', Policy::class);
                    }),
            ]);
    }
}