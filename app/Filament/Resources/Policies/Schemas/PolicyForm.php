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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
                    ->label('Клієнт')
                    ->placeholder('Оберіть клієнта…')
                    ->searchable()
                    ->preload(false)
                    ->getSearchResultsUsing(function (string $search): array {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return Client::query()
                            ->visibleTo($user)
                            ->where(function (Builder $query) use ($search) {
                                $query
                                    ->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('middle_name', 'like', "%{$search}%")
                                    ->orWhere('company_name', 'like', "%{$search}%")
                                    ->orWhere('primary_phone', 'like', "%{$search}%")
                                    ->orWhere('primary_email', 'like', "%{$search}%")
                                    ->orWhere('document_number', 'like', "%{$search}%")
                                    ->orWhere('tax_id', 'like', "%{$search}%");
                            })
                            ->orderBy('last_name')
                            ->orderBy('first_name')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (Client $client) => [
                                $client->id => $client->display_label,
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        if (! $value) {
                            return null;
                        }

                        return Client::query()->find($value)?->display_label;
                    })
                    ->options(function (): array {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return Client::query()
                            ->visibleTo($user)
                            ->orderBy('last_name')
                            ->orderBy('first_name')
                            ->limit(25)
                            ->get()
                            ->mapWithKeys(fn (Client $client) => [
                                $client->id => $client->display_label,
                            ])
                            ->toArray();
                    })
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
                            ->mapWithKeys(function ($offer) {
                                $product = $offer->insuranceProduct?->name ?? 'Продукт';
                                $company = $offer->insuranceCompany?->name ?? 'Компанія';
                                $duration = (int) $offer->duration_months;

                                return [
                                    $offer->id => "{$product} — {$offer->offer_name} — {$company} — {$duration} міс.",
                                ];
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

                        $set(
                            'coverage_amount',
                            $offer?->coverage_amount !== null
                                ? number_format((float) $offer->coverage_amount, 2, '.', '')
                                : null
                        );

                        $rate = (float) ($get('commission_rate') ?? 0);

                        if ($offer) {
                            $base = (float) $offer->price * (int) $offer->duration_months;
                            $premium = $base + ($base * ($rate / 100));
                            $set('premium_amount', number_format($premium, 2, '.', ''));
                        } else {
                            $set('premium_amount', null);
                        }

                        $effectiveDate = $get('effective_date');

                        if ($offer && $effectiveDate) {
                            $expiration = Carbon::parse($effectiveDate)
                                ->addMonths((int) $offer->duration_months)
                                ->format('Y-m-d');

                            $set('expiration_date', $expiration);
                        }
                    })
                    ->afterStateHydrated(function ($state, callable $set, $record) {
                        if (! $record) {
                            return;
                        }

                        $offer = $state ? InsuranceOffer::find($state) : null;

                        if ($offer && $record->effective_date) {
                            $expiration = Carbon::parse($record->effective_date)
                                ->addMonths((int) $offer->duration_months)
                                ->format('Y-m-d');

                            $set('expiration_date', $expiration);
                        }
                    })
                    ->columnSpan(1),

                Select::make('agent_id')
                    ->label('Менеджер')
                    ->options(function () {
                        /** @var User|null $user */
                        $user = Auth::user();

                        if ($user instanceof User && $user->isManager()) {
                            return User::query()
                                ->whereKey($user->id)
                                ->pluck('name', 'id')
                                ->toArray();
                        }

                        return User::query()
                            ->where('is_active', true)
                            ->where('role', 'manager')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->default(fn () => Auth::id())
                    ->disabled(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $user instanceof User && $user->isManager();
                    })
                    ->dehydrated(true)
                    ->visibleOn(CreateRecord::class)
                    ->required()
                    ->rules([
                        Rule::exists('users', 'id')->where(function ($query) {
                            $query
                                ->where('role', 'manager')
                                ->where('is_active', true);
                        }),
                    ])
                    ->validationMessages([
                        'required' => 'Оберіть менеджера.',
                        'exists'   => 'Можна призначити лише активного менеджера.',
                    ])
                    ->columnSpan(1),

                Select::make('agent_id')
                    ->label('Менеджер')
                    ->options(function ($record) {
                        /** @var User|null $user */
                        $user = Auth::user();

                        if ($user instanceof User && $user->isManager()) {
                            return $record && $record->agent
                                ? [$record->agent->id => $record->agent->name]
                                : [];
                        }

                        return User::query()
                            ->where('is_active', true)
                            ->where('role', 'manager')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->disabled(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $user instanceof User && $user->isManager();
                    })
                    ->dehydrated(true)
                    ->visibleOn(EditRecord::class)
                    ->required()
                    ->rules([
                        Rule::exists('users', 'id')->where(function ($query) {
                            $query
                                ->where('role', 'manager')
                                ->where('is_active', true);
                        }),
                    ])
                    ->validationMessages([
                        'required' => 'Оберіть менеджера.',
                        'exists'   => 'Можна призначити лише активного менеджера.',
                    ])
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
                    ->disabled()
                    ->dehydrated(true)
                    ->default('draft')
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
                            $expiration = Carbon::parse($state)
                                ->addMonths((int) $offer->duration_months)
                                ->format('Y-m-d');

                            $set('expiration_date', $expiration);
                        }

                        if ($state) {
                            $set('payments.0.due_date', Carbon::parse($state)->addDays(7)->toDateString());
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
                        $set(
                            'payments.0.amount',
                            $state !== null && $state !== ''
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

                        if (! $offer) {
                            return;
                        }

                        $base = (float) $offer->price * (int) $offer->duration_months;
                        $rate = (float) ($state ?? 0);
                        $premium = $base + ($base * ($rate / 100));

                        $set('premium_amount', number_format($premium, 2, '.', ''));
                        $set('payments.0.amount', number_format($premium, 2, '.', ''));
                    })
                    ->columnSpan(1),

                Textarea::make('notes')
                    ->label('Нотатки')
                    ->rows(3)
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
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
                                $effectiveDate = $get('../../effective_date');
                                $base = $effectiveDate ? Carbon::parse($effectiveDate) : now();

                                return $base->copy()->addDays(7)->toDateString();
                            })
                            ->afterStateHydrated(function ($state, callable $set, Get $get) {
                                if (blank($state)) {
                                    $effectiveDate = $get('../../effective_date');
                                    $base = $effectiveDate ? Carbon::parse($effectiveDate) : now();
                                    $set('due_date', $base->copy()->addDays(7)->toDateString());
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

                                $set(
                                    'amount',
                                    $premium !== null
                                        ? number_format((float) $premium, 2, '.', '')
                                        : number_format(0, 2, '.', '')
                                );
                            })
                            ->dehydrateStateUsing(function ($state, Get $get) {
                                $value = $get('../../premium_amount');

                                return $value !== null && $value !== ''
                                    ? number_format((float) $value, 2, '.', '')
                                    : number_format(0, 2, '.', '');
                            })
                            ->columnSpan(1),
                    ])
                    ->columnSpan(2),
            ]);
    }
}