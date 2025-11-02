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
                    ->preload()
                    ->native(false)
                    ->options(fn () =>
                        Policy::query()
                            ->where('status', 'draft')
                            ->orderBy('policy_number')
                            ->pluck('policy_number', 'id')
                            ->toArray()
                    )
                    ->required()
                    ->rules(['required', 'exists:policies,id'])
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $policy = $state ? Policy::with('insuranceOffer')->find($state) : null;
                        $due = $policy?->effective_date
                            ? Carbon::parse($policy->effective_date)->addDays(7)->format('Y-m-d')
                            : null;
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
                    ->visibleOn(CreateRecord::class)
                    ->afterStateHydrated(function ($state, callable $set, $record) {
                        if (!$record) return;
                        $policy = $state ? Policy::with('insuranceOffer')->find($state) : null;
                        $due = $policy?->effective_date
                            ? Carbon::parse($policy->effective_date)->addDays(7)->format('Y-m-d')
                            : null;
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
                    ->columnSpan(1),

                Select::make('policy_id')
                    ->label('Поліс')
                    ->placeholder('Оберіть поліс…')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function ($record) {
                        $options = Policy::query()
                            ->whereIn('status', ['draft', 'active'])
                            ->orderBy('policy_number')
                            ->pluck('policy_number', 'id')
                            ->toArray();

                        if ($record && $record->policy) {
                            $options[$record->policy->id] = $record->policy->policy_number;
                        }

                        return $options;
                    })
                    ->required()
                    ->rules(['required', 'exists:policies,id'])
                    ->reactive()
                    ->disabled(fn ($record) => in_array($record?->status, ['canceled', 'overdue'], true))
                    ->afterStateUpdated(function ($state, callable $set) {
                        $policy = $state ? Policy::with('insuranceOffer')->find($state) : null;
                        $due = $policy?->effective_date
                            ? Carbon::parse($policy->effective_date)->addDays(7)->format('Y-m-d')
                            : null;
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
                    ->afterStateHydrated(function ($state, callable $set, $record) {
                        $policy = $state ? Policy::with('insuranceOffer')->find($state) : null;
                        $due = $policy?->effective_date
                            ? Carbon::parse($policy->effective_date)->addDays(7)->format('Y-m-d')
                            : null;
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
                    ->visibleOn(EditRecord::class)
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

                Hidden::make('paid_at')
                    ->dehydrateStateUsing(fn ($state, $get) =>
                        $get('status') === 'paid' ? now() : null
                    )
                    ->dehydrated(true),

                TextInput::make('amount')
                    ->label('Сума')
                    ->type('number')
                    ->step('0.01')
                    ->minValue(0)
                    ->readOnly()
                    ->placeholder('0.00')
                    ->suffix('₴')
                    ->required()
                    ->rules(['numeric', 'min:0'])
                    ->dehydrateStateUsing(function ($state, $get) {
                        if ($state !== null && $state !== '') {
                            return number_format((float) $state, 2, '.', '');
                        }
                        $policyId = $get('policy_id');
                        if ($policyId) {
                            $policy = Policy::with('insuranceOffer')->find($policyId);
                            if ($policy && $policy->insuranceOffer) {
                                $total = (float) $policy->insuranceOffer->price * (int) $policy->insuranceOffer->duration_months;
                                $rate  = (float) ($policy->commission_rate ?? 0);
                                $final = $total + $total * ($rate / 100);
                                return number_format($final, 2, '.', '');
                            }
                            if ($policy?->premium_amount !== null) {
                                return number_format((float) $policy->premium_amount, 2, '.', '');
                            }
                        }
                        return number_format(0, 2, '.', '');
                    })
                    ->validationMessages([
                        'required' => 'Вкажіть суму.',
                        'numeric'  => 'Сума має бути числом.',
                        'min'      => 'Сума не може бути відʼємною.',
                    ])
                    ->columnSpan(1),

                Select::make('method')
                    ->label('Метод')
                    ->placeholder('Оберіть метод…')
                    ->options([
                        'card'     => 'Картка',
                        'cash'     => 'Готівка',
                        'transfer' => 'Переказ',
                    ])
                    ->native(false)
                    ->required()
                    ->rules(['required', 'in:card,cash,transfer'])
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        if (in_array($state, ['cash', 'card'], true)) {
                            $set('status', 'paid');
                        } elseif ($state === 'transfer' && $get('status') === null) {
                            $set('status', 'scheduled');
                        }
                    })
                    ->columnSpan(1),

                Select::make('status')
                    ->label('Статус')
                    ->options(fn ($get) =>
                        $get('method') === 'transfer'
                            ? ['scheduled' => 'заплановано', 'paid' => 'сплачено']
                            : ['paid' => 'сплачено']
                    )
                    ->native(false)
                    ->required()
                    ->default('сплачено')
                    ->visibleOn(CreateRecord::class)
                    ->rules(['required', 'in:paid,scheduled'])
                    ->columnSpan(1),

                Select::make('status')
                    ->label('Статус')
                    ->options([
                        'paid'      => 'сплачено',
                        'scheduled' => 'заплановано',
                        'overdue'   => 'прострочено',
                        'canceled'  => 'скасовано',
                    ])
                    ->native(false)
                    ->required()
                    ->visibleOn(EditRecord::class)
                    ->rules(['required', 'in:paid,scheduled,overdue,canceled'])
                    ->columnSpan(1),

                TextInput::make('transaction_reference')
                    ->label('Референс транзакції')
                    ->readOnly()
                    ->dehydrated(false)
                    ->placeholder('Буде згенеровано під час збереження.')
                    ->columnSpan(1),

                Textarea::make('notes')
                    ->label('Нотатки')
                    ->rows(3)
                    ->dehydrateStateUsing(fn ($s) => filled($s) ? $s : null)
                    ->columnSpanFull(),
            ]);
    }
}
