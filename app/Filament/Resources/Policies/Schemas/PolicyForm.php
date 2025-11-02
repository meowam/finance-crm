<?php
namespace App\Filament\Resources\Policies\Schemas;

use App\Models\Client;
use App\Models\InsuranceOffer;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
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
                    })
                    ->columnSpan(1),

                DatePicker::make('expiration_date')
                    ->label('Закінчення')
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
                    ->columnSpan(1),

                TextInput::make('coverage_amount')
                    ->label('Сума покриття')
                    ->type('number')
                    ->step('0.01')
                    ->minValue(0)
                    ->readOnly()
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
                    ->step('0.01')->readOnly()
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
                    })
                    ->columnSpan(1),

                Textarea::make('notes')
                    ->label('Нотатки')
                    ->rows(3)
                    ->dehydrateStateUsing(fn ($s) => filled($s) ? $s : null)
                    ->columnSpanFull(),
            ]);
    }
}
