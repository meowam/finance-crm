<?php

namespace App\Filament\Resources\Policies\Schemas;

use App\Enums\PolicyStatus;
use App\Models\Client;
use App\Models\InsuranceOffer;
use App\Models\Policy;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
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
    protected static function normalizePolicyStatus(mixed $status): string
    {
        return $status instanceof \BackedEnum
            ? (string) $status->value
            : (string) $status;
    }

    protected static function isFutureEditablePolicy(?Policy $record): bool
    {
        return $record instanceof Policy && $record->isEditableBeforeStart();
    }

    protected static function resolveStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'чернетка',
            'active' => 'активний',
            'completed' => 'завершено',
            'canceled' => 'скасовано',
            default => $status,
        };
    }

    protected static function resolveCommissionRate(?InsuranceOffer $offer): float
    {
        if (! $offer) {
            return 0.00;
        }

        return match (mb_strtolower(trim((string) $offer->offer_name))) {
            'базовий', 'базовый', 'basic' => 3.00,
            'комфорт+', 'комфорт плюс', 'comfort+' => 1.50,
            'преміум', 'премиум', 'premium' => 0.00,
            default => 2.00,
        };
    }

    protected static function resolvePremiumAmount(?InsuranceOffer $offer, ?float $rate = null): ?string
    {
        if (! $offer) {
            return null;
        }

        $commissionRate = $rate ?? self::resolveCommissionRate($offer);
        $base = (float) $offer->price * (int) $offer->duration_months;
        $premium = $base + ($base * ($commissionRate / 100));

        return number_format($premium, 2, '.', '');
    }

    protected static function syncInitialPaymentAmount(callable $set, callable $get, ?string $premiumAmount): void
    {
        $payments = $get('payments');

        if (! is_array($payments) || $payments === []) {
            return;
        }

        $normalizedAmount = $premiumAmount !== null && $premiumAmount !== ''
            ? number_format((float) $premiumAmount, 2, '.', '')
            : number_format(0, 2, '.', '');

        foreach (array_keys($payments) as $itemKey) {
            $set("payments.{$itemKey}.amount", $normalizedAmount);
        }
    }

    protected static function syncInitialPaymentDueDate(callable $set, callable $get, ?string $effectiveDate): void
    {
        $payments = $get('payments');

        if (! is_array($payments) || $payments === [] || ! $effectiveDate) {
            return;
        }

        $dueDate = Carbon::parse($effectiveDate)->addDays(7)->toDateString();

        foreach (array_keys($payments) as $itemKey) {
            $set("payments.{$itemKey}.due_date", $dueDate);
        }
    }

    protected static function resolveAssignedManager(?int $clientId, ?User $authUser): ?User
    {
        if ($authUser instanceof User && $authUser->isManager()) {
            return $authUser;
        }

        if (! $clientId) {
            return null;
        }

        $client = Client::query()
            ->with('assignedUser')
            ->find($clientId);

        if (! $client) {
            return null;
        }

        $assignedManager = $client->assignedUser;

        if (! $assignedManager instanceof User) {
            return null;
        }

        if (! $assignedManager->isManager() || ! $assignedManager->is_active) {
            return null;
        }

        return $assignedManager;
    }

    protected static function resolveAssignedManagerId(?int $clientId, ?User $authUser): ?int
    {
        return self::resolveAssignedManager($clientId, $authUser)?->id;
    }

    protected static function resolveAssignedManagerName(?int $clientId, ?User $authUser): ?string
    {
        return self::resolveAssignedManager($clientId, $authUser)?->name;
    }

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

                        return Client::withTrashed()->find($value)?->display_label;
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
                    ->reactive()
                    ->afterStateHydrated(function ($state, callable $set) {
                        /** @var User|null $authUser */
                        $authUser = Auth::user();

                        $clientId = $state ? (int) $state : null;

                        $set('agent_id', self::resolveAssignedManagerId($clientId, $authUser));
                        $set('agent_display', self::resolveAssignedManagerName($clientId, $authUser));
                    })
                    ->afterStateUpdated(function ($state, callable $set) {
                        /** @var User|null $authUser */
                        $authUser = Auth::user();

                        $clientId = $state ? (int) $state : null;

                        $set('agent_id', self::resolveAssignedManagerId($clientId, $authUser));
                        $set('agent_display', self::resolveAssignedManagerName($clientId, $authUser));
                    })
                    ->disabled(fn ($record) => $record !== null)
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

                        $rate = $offer
                            ? self::resolveCommissionRate($offer)
                            : 0.00;

                        $set('commission_rate', number_format($rate, 2, '.', ''));

                        $set(
                            'coverage_amount',
                            $offer?->coverage_amount !== null
                                ? number_format((float) $offer->coverage_amount, 2, '.', '')
                                : null
                        );

                        $premium = self::resolvePremiumAmount($offer, $rate);
                        $set('premium_amount', $premium);

                        self::syncInitialPaymentAmount($set, $get, $premium);

                        $effectiveDate = $get('effective_date');

                        if ($offer && $effectiveDate) {
                            $expiration = Carbon::parse($effectiveDate)
                                ->addMonths((int) $offer->duration_months)
                                ->format('Y-m-d');

                            $set('expiration_date', $expiration);
                        } else {
                            $set('expiration_date', null);
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
                    ->disabled(fn ($record) => $record !== null)
                    ->columnSpan(1),

                Hidden::make('agent_id')
                    ->dehydrated(true)
                    ->rules([
                        Rule::exists('users', 'id')->where(function ($query) {
                            $query
                                ->where('role', 'manager')
                                ->where('is_active', true);
                        }),
                    ])
                    ->validationMessages([
                        'exists' => 'Можна призначити лише активного менеджера.',
                    ]),

                TextInput::make('agent_display')
                    ->label('Менеджер')
                    ->readOnly()
                    ->dehydrated(false)
                    ->placeholder('Буде визначено автоматично')
                    ->afterStateHydrated(function ($state, callable $set, Get $get, $record) {
                        if ($record instanceof Policy && $record->agent) {
                            $set('agent_display', $record->agent->name);
                            return;
                        }

                        /** @var User|null $authUser */
                        $authUser = Auth::user();
                        $clientId = $get('client_id') ? (int) $get('client_id') : null;

                        $set('agent_display', self::resolveAssignedManagerName($clientId, $authUser));
                    })
                    ->columnSpan(1),

                Select::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'чернетка',
                        'active' => 'активний',
                        'completed' => 'завершено',
                        'canceled' => 'скасовано',
                    ])
                    ->native(false)
                    ->disabled()
                    ->dehydrated(true)
                    ->default('draft')
                    ->visibleOn(CreateRecord::class)
                    ->columnSpan(1),

                Select::make('status')
                    ->label('Статус')
                    ->options(function ($record): array {
                        if (! $record instanceof Policy) {
                            return [];
                        }

                        $currentStatus = self::normalizePolicyStatus($record->status);

                        if (! self::isFutureEditablePolicy($record)) {
                            return [
                                $currentStatus => self::resolveStatusLabel($currentStatus),
                            ];
                        }

                        return [
                            $currentStatus => self::resolveStatusLabel($currentStatus),
                            PolicyStatus::Canceled->value => 'скасовано',
                        ];
                    })
                    ->native(false)
                    ->default(fn ($record) => $record ? self::normalizePolicyStatus($record->status) : PolicyStatus::Draft->value)
                    ->disabled(fn ($record) => ! self::isFutureEditablePolicy($record))
                    ->dehydrated(true)
                    ->visibleOn(EditRecord::class)
                    ->required()
                    ->rules([Rule::in(['draft', 'active', 'completed', 'canceled'])])
                    ->columnSpan(1),

                DatePicker::make('effective_date')
                    ->label('Початок дії')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->format('Y-m-d')
                    ->default(fn () => now()->toDateString())
                    ->minDate(fn ($record) => $record ? now()->addDay()->toDateString() : now()->toDateString())
                    ->required()
                    ->reactive()
                    ->disabled(fn ($record) => $record ? ! self::isFutureEditablePolicy($record) : false)
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $offerId = $get('insurance_offer_id');
                        $offer = $offerId ? InsuranceOffer::find($offerId) : null;

                        if ($offer && $state) {
                            $expiration = Carbon::parse($state)
                                ->addMonths((int) $offer->duration_months)
                                ->format('Y-m-d');

                            $set('expiration_date', $expiration);
                        }

                        self::syncInitialPaymentDueDate($set, $get, $state);
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
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        self::syncInitialPaymentAmount(
                            $set,
                            $get,
                            $state !== null && $state !== ''
                                ? number_format((float) $state, 2, '.', '')
                                : number_format(0, 2, '.', '')
                        );
                    })
                    ->disabled(fn ($record) => $record !== null)
                    ->columnSpan(1),

                TextInput::make('coverage_amount')
                    ->label('Сума покриття')
                    ->type('number')
                    ->step('0.01')
                    ->minValue(0)
                    ->readOnly()
                    ->required()
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->disabled(fn ($record) => $record !== null)
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
                        $offer = $offerId ? InsuranceOffer::find($offerId) : null;

                        if (! $offer) {
                            $set('premium_amount', null);
                            self::syncInitialPaymentAmount($set, $get, null);

                            return;
                        }

                        $premium = self::resolvePremiumAmount($offer, (float) ($state ?? 0));

                        $set('premium_amount', $premium);
                        self::syncInitialPaymentAmount($set, $get, $premium);
                    })
                    ->disabled(fn ($record) => $record !== null)
                    ->columnSpan(1),

                Textarea::make('notes')
                    ->label('Нотатки')
                    ->rows(3)
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                    ->disabled(fn ($record) => $record !== null)
                    ->columnSpanFull(),

                Repeater::make('payments')
                    ->label('Оплата при створенні')
                    ->relationship('payments')
                    ->minItems(0)
                    ->maxItems(1)
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->disabled(fn ($record) => $record !== null)
                    ->columns(2)
                    ->schema([
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
                            ->afterStateUpdated(function ($state, callable $set) {
                                $paymentStatus = match ($state) {
                                    'card', 'cash' => 'paid',
                                    'transfer' => 'scheduled',
                                    default => 'draft',
                                };

                                $set('status', $paymentStatus);
                            })
                            ->columnSpan(1),

                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'чернетка',
                                'scheduled' => 'заплановано',
                                'paid' => 'сплачено',
                                'canceled' => 'скасовано',
                            ])
                            ->disabled()
                            ->dehydrated(true)
                            ->afterStateHydrated(function ($state, callable $set, $record, $get) {
                                $method = $get('method') ?? 'no_method';

                                $paymentStatus = match ($method) {
                                    'card', 'cash' => 'paid',
                                    'transfer' => 'scheduled',
                                    default => 'draft',
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