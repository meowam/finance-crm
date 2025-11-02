<?php

namespace App\Filament\Resources\Policies\Schemas;

use App\Models\Client;
use App\Models\InsuranceOffer;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('policy_number')
                    ->label('Номер полісу')
                    ->readOnly()
                    ->dehydrated(false)
                    ->placeholder('Буде згенеровано під час збереження.')
                    ->columnSpan(1),

                Select::make('client_id')
                    ->label('Клієнт (email)')
                    ->placeholder('Оберіть клієнта…')
                    ->options(fn () =>
                        Client::query()
                            ->orderBy('primary_email')
                            ->pluck('primary_email', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required()
                    ->rules(['required', 'exists:clients,id'])
                    ->columnSpan(1),

                Select::make('insurance_offer_id')
                    ->label('Страховий продукт')
                    ->placeholder('Оберіть продукт…')
                    ->options(function () {
                        return InsuranceOffer::query()
                            ->with(['insuranceProduct:id,name', 'insuranceCompany:id,name'])
                            ->get()
                            ->mapWithKeys(function ($o) {
                                $prod = $o->insuranceProduct?->name ?? 'Продукт';
                                $comp = $o->insuranceCompany?->name ?? 'Компанія';
                                $dur  = (int) $o->duration_months;
                                $label = "{$prod} — {$o->offer_name} — {$comp} — {$dur} міс.";
                                return [$o->id => $label];
                            })
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required()
                    ->rules(['required', 'exists:insurance_offers,id'])
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $offer = $state ? InsuranceOffer::with('insuranceProduct')->find($state) : null;

                        if ($offer) {
                            $autoRate = match ($offer->offer_name) {
                                'Базовий'  => 3.00,
                                'Комфорт+' => 1.50,
                                'Преміум'  => 0.50,
                                default    => 2.00,
                            };
                            $set('commission_rate', number_format($autoRate, 2, '.', ''));
                        }

                        $set('coverage_amount', $offer?->coverage_amount !== null
                            ? number_format((float) $offer->coverage_amount, 2, '.', '')
                            : null
                        );

                        $rate = (float) ($get('commission_rate') ?? 0);
                        if ($offer) {
                            $base = (float) $offer->price * (int) $offer->duration_months;
                            $prem = $base + ($base * ($rate / 100));
                            $set('premium_amount', number_format($prem, 2, '.', ''));
                        } else {
                            $set('premium_amount', null);
                        }

                        $eff = $get('effective_date');
                        if ($offer && $eff) {
                            $exp = Carbon::parse($eff)->addMonths((int) $offer->duration_months)->format('Y-m-d');
                            $set('expiration_date', $exp);
                        }
                    })
                    ->afterStateHydrated(function ($state, callable $set, $record) {
                        if (!$record) {
                            return;
                        }
                        $offer = $state ? InsuranceOffer::find($state) : null;
                        if ($offer && $record->effective_date) {
                            $exp = Carbon::parse($record->effective_date)->addMonths((int) $offer->duration_months)->format('Y-m-d');
                            $set('expiration_date', $exp);
                        }
                    })
                    ->columnSpan(1),

                Select::make('agent_id')
                    ->label('Менеджер')
                    ->options(fn () => User::query()
                        ->whereKey(Auth::id())
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->default(fn () => Auth::id())
                    ->disabled()
                    ->dehydrated(true)
                    ->visibleOn(CreateRecord::class)
                    ->columnSpan(1),

                Select::make('agent_id')
                    ->label('Менеджер')
                    ->options(fn ($record) => $record && $record->agent
                        ? [$record->agent->id => $record->agent->name]
                        : []
                    )
                    ->disabled()
                    ->dehydrated(true)
                    ->visibleOn(EditRecord::class)
                    ->columnSpan(1),

                Select::make('status')
                    ->label('Статус')
                    ->options([
                        'draft'     => 'чернетка',
                        'active'    => 'активний',
                        'completed' => 'завершено',
                        'canceled'  => 'скасовано',
                    ])
                    ->native(false)
                    ->required()
                    ->default('draft')
                    ->rules(['required', 'in:draft,active,completed,canceled'])
                    ->disabled()       
                    ->dehydrated(true) 
                    ->reactive()
                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                        $paymentStatus = $get('payments.0.status') ?? 'draft';
                        $policyStatus = match ($paymentStatus) {
                            'paid' => 'active',
                            'scheduled', 'draft' => 'draft',
                            default => 'draft',
                        };
                        $set('status', $policyStatus);
                    })
                    ->columnSpan(1),

                DatePicker::make('effective_date')
                    ->label('Початок дії')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->format('Y-m-d')
                    ->default(fn () => now()->toDateString())
                    ->minDate(fn () => now()->toDateString())
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $offerId = $get('insurance_offer_id');
                        $offer   = $offerId ? InsuranceOffer::find($offerId) : null;
                        if ($offer && $state) {
                            $exp = Carbon::parse($state)->addMonths((int) $offer->duration_months)->format('Y-m-d');
                            $set('expiration_date', $exp);
                        }
                        if ($state) {
                            $set('payments.0.due_date', Carbon::parse($state)->addDays(rand(5,7))->toDateString());
                        }
                    })
                    ->columnSpan(1),

                DatePicker::make('expiration_date')
                    ->label('Закінчення дії')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->format('Y-m-d')
                    ->readOnly()
                    ->required()
                    ->rules(['required', 'date'])
                    ->columnSpan(1),

                TextInput::make('premium_amount')
                    ->label('Сума до оплати')
                    ->type('number')
                    ->step('0.01')
                    ->minValue(0)
                    ->readOnly()
                    ->required()
                    ->rules(['numeric', 'min:0'])
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('payments.0.amount', $state !== null && $state !== ''
                            ? number_format((float) $state, 2, '.', '')
                            : number_format(0, 2, '.', '')
                        );
                    })
                    ->columnSpan(1),

                TextInput::make('coverage_amount')
                    ->label('Сума покриття')
                    ->type('number')
                    ->step('0.01')
                    ->minValue(0)
                    ->readOnly()
                    ->required()
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->columnSpan(1),

                Select::make('payment_frequency')
                    ->label('Періодичність оплати')
                    ->options(['once' => 'одноразово'])
                    ->default('once')
                    ->disabled()
                    ->dehydrated(true)
                    ->columnSpan(1),

                TextInput::make('commission_rate')
                    ->label('Комісія, %')
                    ->type('number')
                    ->step('0.01')
                    ->readOnly()
                    ->minValue(0)
                    ->required()
                    ->rules(['numeric', 'min:0'])
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $offerId = $get('insurance_offer_id');
                        $offer   = $offerId ? InsuranceOffer::find($offerId) : null;
                        if (!$offer) {
                            return;
                        }
                        $base = (float) $offer->price * (int) $offer->duration_months;
                        $rate = (float) ($state ?? 0);
                        $prem = $base + ($base * ($rate / 100));
                        $set('premium_amount', number_format($prem, 2, '.', ''));
                        $set('payments.0.amount', number_format($prem, 2, '.', ''));
                    })
                    ->columnSpan(1),

                Textarea::make('notes')
                    ->label('Нотатки')
                    ->rows(3)
                    ->dehydrateStateUsing(fn ($s) => filled($s) ? $s : null)
                    ->columnSpanFull(),

                Repeater::make('payments')
                    ->label('Оплата при створенні')
                    ->relationship('payments')
                    ->minItems(0)
                    ->maxItems(1)
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->columns(2)
                    ->schema([
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
                            ->afterStateUpdated(function ($state, callable $set) {
                                $paymentStatus = match ($state) {
                                    'card', 'cash' => 'paid',
                                    'transfer'     => 'scheduled',
                                    default        => 'draft',
                                };
                                $set('status', $paymentStatus);
                                $policyStatus = $paymentStatus === 'paid' ? 'active' : 'draft';
                                $set('../../status', $policyStatus);
                            })
                            ->columnSpan(1),

                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft'     => 'чернетка',
                                'scheduled' => 'заплановано',
                                'paid'      => 'сплачено',
                                'canceled'  => 'скасовано',
                            ])
                            ->disabled()
                            ->dehydrated(true)
                            ->afterStateHydrated(function ($state, callable $set, $record, $get) {
                                $method = $get('method') ?? 'no_method';
                                $paymentStatus = match ($method) {
                                    'card', 'cash' => 'paid',
                                    'transfer'     => 'scheduled',
                                    default        => 'draft',
                                };
                                $set('status', $paymentStatus);

                                $policyStatus = $paymentStatus === 'paid' ? 'active' : 'draft';
                                $set('../../status', $policyStatus);
                            })
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $policyStatus = $state === 'paid' ? 'active' : 'draft';
                                $set('../../status', $policyStatus);
                            })
                            ->columnSpan(1),

                        DatePicker::make('due_date')
                            ->label('Строк оплати')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->format('Y-m-d')
                            ->readOnly()
                            ->required()
                            ->default(function (Get $get) {
                                $eff = $get('../../effective_date');
                                $base = $eff ? Carbon::parse($eff) : now();
                                return $base->copy()->addDays(rand(5,7))->toDateString();
                            })
                            ->afterStateHydrated(function ($state, callable $set, Get $get) {
                                if (blank($state)) {
                                    $eff = $get('../../effective_date');
                                    $base = $eff ? Carbon::parse($eff) : now();
                                    $set('due_date', $base->copy()->addDays(rand(5,7))->toDateString());
                                }
                            })
                            ->columnSpan(1),

                        TextInput::make('amount')
                            ->label('Сума')
                            ->type('number')
                            ->step('0.01')
                            ->minValue(0)
                            ->readOnly()
                            ->required()
                            ->rules(['numeric', 'min:0'])
                            ->afterStateHydrated(function ($state, callable $set, Get $get) {
                                if ($state !== null && $state !== '') {
                                    return;
                                }
                                $premium = $get('../../premium_amount');
                                $set('amount', $premium !== null ? number_format((float) $premium, 2, '.', '') : number_format(0, 2, '.', ''));
                            })
                            ->dehydrateStateUsing(function ($state, Get $get) {
                                $val = $get('../../premium_amount');
                                return $val !== null && $val !== '' ? number_format((float) $val, 2, '.', '') : number_format(0, 2, '.', '');
                            })
                            ->columnSpan(1),
                    ])
                    ->columnSpan(2),
            ]);
    }
}
