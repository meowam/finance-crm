<?php

namespace App\Filament\Resources\PolicyPayments\Schemas;

use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PolicyPaymentForm
{
    protected static function availablePoliciesQuery(?User $user): Builder
    {
        return Policy::query()
            ->select('id', 'policy_number')
            ->visibleTo($user)
            ->where('status', 'draft')
            ->whereDoesntHave('payments', function (Builder $query) {
                $query->whereIn('status', PolicyPayment::ACTIVE_STATUSES);
            });
    }

    protected static function resolveDueDate(?Policy $policy): string
    {
        if ($policy?->payment_due_at) {
            return Carbon::parse($policy->payment_due_at)->toDateString();
        }

        $baseDate = $policy?->effective_date ?: now()->toDateString();

        return Carbon::parse($baseDate)->addDays(7)->toDateString();
    }

    protected static function resolveAmount(?Policy $policy): string
    {
        if (! $policy) {
            return number_format(0, 2, '.', '');
        }

        if ($policy->premium_amount !== null) {
            return number_format((float) $policy->premium_amount, 2, '.', '');
        }

        return number_format(0, 2, '.', '');
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('policy_id')
                    ->label('Поліс')
                    ->placeholder('Оберіть поліс…')
                    ->searchable()
                    ->preload(false)
                    ->options(function () {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return self::availablePoliciesQuery($user)
                            ->orderBy('policy_number')
                            ->limit(50)
                            ->pluck('policy_number', 'id')
                            ->toArray();
                    })
                    ->getSearchResultsUsing(function (string $search) {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return self::availablePoliciesQuery($user)
                            ->when($search !== '', fn (Builder $query) => $query->where('policy_number', 'like', "%{$search}%"))
                            ->orderBy('policy_number')
                            ->limit(50)
                            ->pluck('policy_number', 'id')
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value) {
                        if (! $value) {
                            return null;
                        }

                        /** @var User|null $user */
                        $user = Auth::user();

                        return Policy::query()
                            ->visibleTo($user)
                            ->whereKey($value)
                            ->value('policy_number');
                    })
                    ->required()
                    ->rules(['required', 'exists:policies,id'])
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        /** @var User|null $user */
                        $user = Auth::user();

                        $policy = $state
                            ? Policy::query()->visibleTo($user)->find($state)
                            : null;

                        $set('due_date', self::resolveDueDate($policy));
                        $set('amount', self::resolveAmount($policy));
                    })
                    ->disabled(fn ($record) => $record && in_array(
                        mb_strtolower((string) ($record->status instanceof \BackedEnum ? $record->status->value : $record->status)),
                        ['paid', 'overdue', 'refunded'],
                        true
                    ))
                    ->visibleOn(CreateRecord::class)
                    ->columnSpan(1),

                Hidden::make('policy_id')
                    ->visibleOn(EditRecord::class)
                    ->dehydrated(true),

                TextInput::make('policy_number')
                    ->label('Номер полісу')
                    ->readOnly()
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, callable $set, $record, $get) {
                        $set('policy_number', $record?->policy?->policy_number ?? '—');

                        if (blank($get('due_date')) && $record?->policy) {
                            $set('due_date', self::resolveDueDate($record->policy));
                        }
                    })
                    ->visibleOn(EditRecord::class)
                    ->columnSpan(1),

                TextInput::make('transaction_reference')
                    ->label('Номер транзакції')
                    ->readOnly()
                    ->dehydrated(false)
                    ->placeholder('Буде згенеровано під час збереження.')
                    ->columnSpan(1),

                DatePicker::make('due_date')
                    ->label('Строк оплати')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->format('Y-m-d')
                    ->readOnly()
                    ->required()
                    ->rules(['required', 'date'])
                    ->afterStateHydrated(function ($state, callable $set, $record, $get) {
                        if (! blank($state)) {
                            return;
                        }

                        /** @var User|null $user */
                        $user = Auth::user();

                        $policy = null;
                        $policyId = $record?->policy_id ?? $get('policy_id');

                        if ($policyId) {
                            $policy = Policy::query()->visibleTo($user)->find($policyId);
                        }

                        $set('due_date', self::resolveDueDate($policy));
                    })
                    ->columnSpan(1),

                TextInput::make('amount')
                    ->label('Сума')
                    ->type('number')
                    ->step('0.01')
                    ->minValue(0)
                    ->placeholder('0.00')
                    ->suffix('₴')
                    ->readOnly()
                    ->required()
                    ->rules(['numeric', 'min:0'])
                    ->reactive()
                    ->afterStateHydrated(function ($state, callable $set, $record, $get) {
                        if ($state !== null && $state !== '') {
                            return;
                        }

                        /** @var User|null $user */
                        $user = Auth::user();

                        $policy = null;
                        $policyId = $record?->policy_id ?? $get('policy_id');

                        if ($policyId) {
                            $policy = Policy::query()->visibleTo($user)->find($policyId);
                        }

                        $set('amount', self::resolveAmount($policy));
                    })
                    ->dehydrateStateUsing(fn ($state) => $state !== null && $state !== ''
                        ? number_format((float) $state, 2, '.', '')
                        : number_format(0, 2, '.', '')
                    )
                    ->disabled(fn ($record) => $record && in_array(
                        mb_strtolower((string) ($record->status instanceof \BackedEnum ? $record->status->value : $record->status)),
                        ['paid', 'overdue', 'refunded'],
                        true
                    ))
                    ->columnSpan(1),

                Select::make('method')
                    ->label('Метод')
                    ->placeholder('Оберіть метод…')
                    ->options([
                        'no_method' => 'Не вибрано',
                        'card' => 'Картка',
                        'cash' => 'Готівка',
                        'transfer' => 'Переказ',
                    ])
                    ->native(false)
                    ->required()
                    ->rules(['required', 'in:no_method,card,cash,transfer'])
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        if (in_array($state, ['cash', 'card'], true)) {
                            $set('status', 'paid');
                            $set('initiated_at', null);
                            $set('paid_at', now());
                            return;
                        }

                        if ($state === 'transfer') {
                            if (! in_array($get('status'), ['scheduled', 'paid', 'canceled', 'refunded'], true)) {
                                $set('status', 'scheduled');
                            }
                            $set('initiated_at', $get('initiated_at') ?: now());
                            if ($get('status') !== 'paid') {
                                $set('paid_at', null);
                            }
                            return;
                        }

                        $set('status', 'draft');
                        $set('initiated_at', null);
                        $set('paid_at', null);
                    })
                    ->disabled(fn ($record) => $record && in_array(
                        mb_strtolower((string) ($record->status instanceof \BackedEnum ? $record->status->value : $record->status)),
                        ['paid', 'overdue', 'refunded'],
                        true
                    ))
                    ->columnSpan(1),

                Select::make('status')
                    ->label('Статус')
                    ->options(fn ($get) => match ($get('method')) {
                        'transfer' => [
                            'scheduled' => 'заплановано',
                            'paid' => 'сплачено',
                            'canceled' => 'скасовано',
                            'refunded' => 'повернено',
                        ],
                        'card', 'cash' => [
                            'paid' => 'сплачено',
                            'canceled' => 'скасовано',
                            'refunded' => 'повернено',
                        ],
                        'no_method' => [
                            'draft' => 'чернетка',
                            'canceled' => 'скасовано',
                        ],
                        default => ['draft' => 'чернетка'],
                    })
                    ->native(false)
                    ->required()
                    ->default(fn ($get) => match ($get('method')) {
                        'transfer' => 'scheduled',
                        'card', 'cash' => 'paid',
                        'no_method' => 'draft',
                        default => 'draft',
                    })
                    ->rules(['required', 'in:draft,scheduled,paid,overdue,canceled,refunded'])
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $method = $get('method');

                        if (in_array($method, ['cash', 'card'], true)) {
                            $set('paid_at', $state === 'paid' ? ($get('paid_at') ?: now()) : $get('paid_at'));
                            $set('initiated_at', null);
                            return;
                        }

                        if ($method === 'transfer') {
                            if ($state === 'scheduled') {
                                $set('initiated_at', $get('initiated_at') ?: now());
                                $set('paid_at', null);
                            } elseif ($state === 'paid') {
                                $set('paid_at', $get('paid_at') ?: now());
                            }
                            return;
                        }

                        if ($method === 'no_method' && $state === 'draft') {
                            $set('initiated_at', null);
                            $set('paid_at', null);
                        }
                    })
                    ->disabled(fn ($record) => $record && in_array(
                        mb_strtolower((string) ($record->status instanceof \BackedEnum ? $record->status->value : $record->status)),
                        ['paid', 'overdue', 'refunded'],
                        true
                    ))
                    ->columnSpan(1),

                Hidden::make('paid_at')
                    ->dehydrateStateUsing(fn ($state, $get) => $get('status') === 'paid' ? ($state ?: now()) : $state)
                    ->dehydrated(true),

                Hidden::make('initiated_at')
                    ->dehydrateStateUsing(fn ($state, $get) =>
                        $get('method') === 'transfer'
                            ? ($state ?: ($get('status') === 'scheduled' ? now() : null))
                            : null
                    )
                    ->dehydrated(true),

                Textarea::make('notes')
                    ->label('Нотатки')
                    ->rows(3)
                    ->dehydrateStateUsing(fn ($s) => filled($s) ? $s : null)
                    ->disabled(fn ($record) => $record && in_array(
                        mb_strtolower((string) ($record->status instanceof \BackedEnum ? $record->status->value : $record->status)),
                        ['paid', 'overdue', 'refunded'],
                        true
                    ))
                    ->columnSpanFull(),
            ]);
    }
}