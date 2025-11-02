<?php
namespace App\Filament\Resources\PolicyPayments\Schemas;

use App\Models\Policy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class PolicyPaymentForm
{
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
                    ->options(fn() =>

                        Policy::query()
                            ->select('id', 'policy_number')
                            ->where('status', 'draft')
                            ->whereDoesntHave('payments', fn($q) => $q->whereNotNull('blocking_policy_id'))
                            ->orderBy('policy_number')
                            ->limit(50)
                            ->pluck('policy_number', 'id')
                            ->toArray()
                    )
                    ->getSearchResultsUsing(function (string $search) {
                        return Policy::query()
                            ->select('id', 'policy_number')
                            ->where('status', 'draft')
                            ->whereDoesntHave('payments', fn($q) => $q->whereNotNull('blocking_policy_id'))
                            ->when($search !== '', fn($q) =>
                                $q->where('policy_number', 'like', "%{$search}%")
                            )
                            ->orderBy('policy_number')
                            ->limit(50)
                            ->pluck('policy_number', 'id')
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value) {
                        if (! $value) {
                            return null;
                        }

                        return Policy::whereKey($value)->value('policy_number');
                    })
                    ->required()
                    ->rules(['required', 'exists:policies,id'])
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $policy = $state ? Policy::with('insuranceOffer')->find($state) : null;

                        $due = now()->addDays(rand(5, 7))->format('Y-m-d');
                        if ($policy?->effective_date) {
                            $due = Carbon::parse($policy->created_at ?? now())
                                ->addDays(rand(5, 7))
                                ->format('Y-m-d');
                        }
                        $set('due_date', $due);

                        $final = null;
                        if ($policy && $policy->insuranceOffer) {
                            $offer = $policy->insuranceOffer;
                            $total = round((float) $offer->price * (int) $offer->duration_months, 2);
                            $rate  = (float) ($policy->commission_rate ?? 0);
                            $final = round($total + $total * ($rate / 100), 2);
                        }
                        if ($final === null && $policy?->premium_amount !== null) {
                            $final = (float) $policy->premium_amount;
                        }
                        $set('amount', $final !== null ? number_format($final, 2, '.', '') : null);
                    })
                    ->disabled(fn($record) => $record && in_array(
                        mb_strtolower((string) ($record->status instanceof \BackedEnum  ? $record->status->value : $record->status)),
                        ['paid', 'overdue'],
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
                            $base = Carbon::parse($record->policy->created_at ?? now());
                            $set('due_date', $base->copy()->addDays(rand(5, 7))->format('Y-m-d'));
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

                        $policyId = $get('policy_id');
                        if ($policyId) {
                            $policy = Policy::with('insuranceOffer')->find($policyId);
                            if ($policy && $policy->insuranceOffer) {
                                $total = (float) $policy->insuranceOffer->price * (int) $policy->insuranceOffer->duration_months;
                                $rate  = (float) ($policy->commission_rate ?? 0);
                                $final = round($total + $total * ($rate / 100), 2);
                                $set('amount', number_format($final, 2, '.', ''));
                                return;
                            }
                            if ($policy?->premium_amount !== null) {
                                $set('amount', number_format((float) $policy->premium_amount, 2, '.', ''));
                                return;
                            }
                        }

                        $set('amount', number_format(0, 2, '.', ''));
                    })
                    ->dehydrateStateUsing(fn($state) => $state !== null && $state !== '' ? number_format((float) $state, 2, '.', '') : number_format(0, 2, '.', ''))
                    ->disabled(fn($record) => $record && in_array(
                        mb_strtolower((string) ($record->status instanceof \BackedEnum  ? $record->status->value : $record->status)),
                        ['paid', 'overdue'],
                        true
                    ))
                    ->columnSpan(1),

                Select::make('method')
                    ->label('Метод')
                    ->placeholder('Оберіть метод…')
                    ->options([
                        'no_method' => 'Не вибрано',
                        'card'      => 'Картка',
                        'cash'      => 'Готівка',
                        'transfer'  => 'Переказ',
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
                            if (! in_array($get('status'), ['scheduled', 'paid', 'canceled'], true)) {
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
                    ->disabled(fn($record) => $record && in_array(
                        mb_strtolower((string) ($record->status instanceof \BackedEnum  ? $record->status->value : $record->status)),
                        ['paid', 'overdue'],
                        true
                    ))
                    ->columnSpan(1),

                Select::make('status')
                    ->label('Статус')
                    ->options(fn($get) => match ($get('method')) {
                        'transfer'  => ['scheduled' => 'заплановано', 'paid' => 'сплачено', 'canceled' => 'скасовано'],
                        'card', 'cash' => ['paid' => 'сплачено', 'canceled' => 'скасовано'],
                        'no_method' => ['draft' => 'чернетка', 'canceled' => 'скасовано'],
                        default     => ['draft' => 'чернетка'],
                    })
                    ->native(false)
                    ->required()
                    ->default(fn($get) => match ($get('method')) {
                        'transfer'  => 'scheduled',
                        'card', 'cash' => 'paid',
                        'no_method' => 'draft',
                        default     => 'draft',
                    })
                    ->rules(['required', 'in:draft,scheduled,paid,overdue,canceled'])
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $method = $get('method');

                        if (in_array($method, ['cash', 'card'], true)) {
                            $set('paid_at', $state === 'paid' ? ($get('paid_at') ?: now()): null);
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

                        if ($method === 'no_method') {
                            if ($state === 'draft') {
                                $set('initiated_at', null);
                                $set('paid_at', null);
                            }
                        }
                    })
                    ->disabled(fn($record) => $record && in_array(
                        mb_strtolower((string) ($record->status instanceof \BackedEnum  ? $record->status->value : $record->status)),
                        ['paid', 'overdue'],
                        true
                    ))
                    ->columnSpan(1),

                Hidden::make('paid_at')
                    ->dehydrateStateUsing(fn($state, $get) =>
                        $get('status') === 'paid' ? ($state ?: now()): null
                    )
                    ->dehydrated(true),

                Hidden::make('initiated_at')
                    ->dehydrateStateUsing(fn($state, $get) =>
                        $get('method') === 'transfer'
                            ? ($state ?: ($get('status') === 'scheduled' ? now() : null))
                            : null
                    )
                    ->dehydrated(true),

                Textarea::make('notes')
                    ->label('Нотатки')
                    ->rows(3)
                    ->dehydrateStateUsing(fn($s) => filled($s) ? $s : null)
                    ->disabled(fn($record) => $record && in_array(
                        mb_strtolower((string) ($record->status instanceof \BackedEnum  ? $record->status->value : $record->status)),
                        ['paid', 'overdue'],
                        true
                    ))
                    ->columnSpanFull(),
            ]);
    }
}
