<?php
namespace App\Filament\Resources\InsuranceOffers\Schemas;

use App\Models\InsuranceCompany;
use App\Models\InsuranceProduct;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset as ComponentsFieldset;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class InsuranceOfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('insurance_product_id')
                    ->label('Страховий продукт')
                    ->placeholder('Оберіть продукт…')
                    ->options(fn () => InsuranceProduct::query()
                        ->where('sales_enabled', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required()
                    ->rules(['required', 'exists:insurance_products,id'])
                    ->validationMessages([
                        'required' => 'Оберіть страховий продукт.',
                        'exists'   => 'Обраний продукт не знайдено.',
                    ])
                    ->columnSpan(1),

                Select::make('insurance_company_id')
                    ->label('Страхова компанія')
                    ->placeholder('Оберіть компанію…')
                    ->options(fn () => InsuranceCompany::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required()
                    ->rules(['required', 'exists:insurance_companies,id'])
                    ->validationMessages([
                        'required' => 'Оберіть страхову компанію.',
                        'exists'   => 'Обрану компанію не знайдено.',
                    ])
                    ->columnSpan(1),

                Select::make('offer_name')
                    ->label('Назва пропозиції')
                    ->options([
                        'Базовий'  => 'Базовий',
                        'Комфорт+' => 'Комфорт+',
                        'Преміум'  => 'Преміум',
                    ])
                    ->native(false)
                    ->required()
                    ->rules(fn ($get, $record) => [
                        Rule::unique('insurance_offers', 'offer_name')
                            ->where(fn ($q) => $q
                                ->where('insurance_company_id', $get('insurance_company_id'))
                                ->where('insurance_product_id', $get('insurance_product_id'))
                                ->where('duration_months', $get('duration_months'))
                            )
                            ->ignore($record?->id),
                    ])
                    ->validationMessages([
                        'required' => 'Оберіть назву пропозиції.',
                        'unique'   => 'Упс — така пропозиція вже існує для цієї компанії, продукту та тривалості.',
                    ])
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $price = is_numeric($get('price')) ? (float) $get('price') : 0.0;

                        $map = [
                            'Базовий'  => ['coverage' => 35, 'franchise' => 0.15],
                            'Комфорт+' => ['coverage' => 60, 'franchise' => 0.07],
                            'Преміум'  => ['coverage' => 90, 'franchise' => 0.00],
                        ];
                        $t = $map[$state] ?? null;
                        if (! $t) {
                            return;
                        }

                        $set('coverage_amount', number_format($price * $t['coverage'], 2, '.', ''));
                        $set('franchise', number_format($price * $t['franchise'], 2, '.', ''));

                        $set('sw_base', true);
                        $set('sw_fast_online', true);
                        $set('sw_e_policy', true);
                        $set('sw_road', in_array($state, ['Комфорт+', 'Преміум']));
                        $set('sw_vip', $state === 'Преміум');
                        $set('sw_cashback', $state === 'Преміум');
                        $set('sw_no_franchise', $state === 'Преміум');

                        $benefKeys = [];
                        $condKeys  = ['support_24_7'];

                        $benefKeys[] = 'Базове покриття основних ризиків';
                        $benefKeys[] = 'Швидке онлайн-оформлення';
                        $benefKeys[] = 'Електронний поліс';
                        $condKeys[]  = 'documents_e';

                        if (in_array($state, ['Комфорт+', 'Преміум'])) {
                            $benefKeys[] = 'Допомога на дорозі';
                            $condKeys[]  = 'road_assistance';
                        }
                        if ($state === 'Преміум') {
                            $benefKeys[] = 'VIP-підтримка';
                            $benefKeys[] = 'Кешбек 5%';
                            $benefKeys[] = 'Без франшизи';
                            $condKeys[]  = 'vip_service';
                            $condKeys[]  = 'cashback_5';
                        }

                        $set('benefits_json', json_encode($benefKeys, JSON_UNESCAPED_UNICODE));
                        $set('conditions_arr', $condKeys);
                    })
                    ->columnSpan(1),

                Select::make('duration_months')
                    ->label('Тривалість')
                    ->options([
                        1  => '1 міс.',
                        3  => '3 міс.',
                        6  => '6 міс.',
                        12 => '12 міс.',
                    ])
                    ->native(false)
                    ->required()
                    ->default(3)
                    ->rules(['required', 'in:1,3,6,12'])
                    ->validationMessages([
                        'required' => 'Оберіть тривалість.',
                        'in'       => 'Допустимі значення: 1, 3, 6 або 12 місяців.',
                    ])
                    ->columnSpan(1),


                TextInput::make('price')
                    ->label('Ціна')
                    ->type('number')
                    ->step('0.01')
                    ->minValue(0)
                    ->placeholder('0.00')
                    ->suffix('₴')
                    ->required()
                    ->dehydrated(true)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $price  = is_numeric($state) ? (float) $state : 0.0;
                        $tariff = $get('offer_name');

                        $map = [
                            'Базовий'  => ['coverage' => 35, 'franchise' => 0.15],
                            'Комфорт+' => ['coverage' => 60, 'franchise' => 0.07],
                            'Преміум'  => ['coverage' => 90, 'franchise' => 0.00],
                        ];
                        $t = $map[$tariff] ?? null;
                        if (! $t) {
                            return;
                        }

                        $set('coverage_amount', number_format($price * $t['coverage'], 2, '.', ''));
                        $set('franchise', number_format($price * $t['franchise'], 2, '.', ''));
                        $set('sw_no_franchise', $tariff === 'Преміум');
                    })
                    ->validationMessages([
                        'required' => 'Вкажіть ціну.',
                    ])
                    ->columnSpan(1),

                TextInput::make('coverage_amount')
                    ->label('Сума покриття')
                    ->suffix('₴')
                    ->readOnly()
                    ->required()
                    ->rule('regex:/^\d+(?:[.]\d{0,2})?$/')
                    ->validationMessages([
                        'required' => 'Поле «Сума покриття» обовʼязкове.',
                    ])
                    ->columnSpan(1),

                TextInput::make('franchise')
                    ->label('Франшиза')
                    ->suffix('₴')
                    ->readOnly()
                    ->required()
                    ->rule('regex:/^\d+(?:[.]\d{0,2})?$/')
                    ->validationMessages([
                        'required' => 'Поле «Франшиза» обовʼязкове.',
                    ])
                    ->columnSpan(1),

                ComponentsFieldset::make('Опції тарифу')
                    ->columns(3)
                    ->schema([
                        Toggle::make('sw_base')->label('Базове покриття')->disabled()->dehydrated(false),
                        Toggle::make('sw_fast_online')->label('Швидке онлайн-оформлення')->disabled()->dehydrated(false),
                        Toggle::make('sw_e_policy')->label('Електронний поліс')->disabled()->dehydrated(false),
                        Toggle::make('sw_road')->label('Допомога на дорозі')->disabled()->dehydrated(false),
                        Toggle::make('sw_vip')->label('VIP-підтримка')->disabled()->dehydrated(false),
                        Toggle::make('sw_cashback')->label('Кешбек 5%')->disabled()->dehydrated(false),
                        Toggle::make('sw_no_franchise')->label('Без франшизи')->disabled()->dehydrated(false),
                    ])
                    ->columnSpan(2),

                Textarea::make('benefits_json')
                    ->dehydrateStateUsing(fn ($s, $get) => $get('benefits_json'))
                    ->afterStateHydrated(function ($state, callable $set, $record) {
                        if ($record?->benefits) {
                            $set('benefits_json', is_array($record->benefits)
                                ? json_encode($record->benefits, JSON_UNESCAPED_UNICODE)
                                : (string) $record->benefits);
                        }
                    })
                    ->visible(false)
                    ->dehydrated(true)
                    ->label('')
                    ->columnSpanFull(),

                Textarea::make('benefits')
                    ->visible(false)
                    ->dehydrateStateUsing(fn ($state, $get) => $get('benefits_json'))
                    ->dehydrated(true)
                    ->label('')
                    ->columnSpanFull(),

                Textarea::make('conditions')
                    ->visible(false)
                    ->afterStateHydrated(function ($state, callable $set, $record) {
                        if ($record?->conditions) {
                            $set('conditions_arr', $record->conditions);
                        }
                    })
                    ->dehydrateStateUsing(fn ($state, $get) => json_encode($get('conditions_arr') ?? [], JSON_UNESCAPED_UNICODE))
                    ->dehydrated(true)
                    ->label('')
                    ->columnSpanFull(),

                Textarea::make('conditions_arr')
                    ->visible(false)
                    ->dehydrated(false)
                    ->label('')
                    ->columnSpanFull(),
            ]);
    }
}
