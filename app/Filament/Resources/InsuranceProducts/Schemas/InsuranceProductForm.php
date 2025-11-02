<?php

namespace App\Filament\Resources\InsuranceProducts\Schemas;

use App\Models\InsuranceCategory;
use App\Models\InsuranceProduct;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InsuranceProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $categories = InsuranceCategory::query()
            ->orderBy('name')
            ->get(['id','name','code']);

        $categoryOptions = $categories->pluck('name', 'id')->toArray();
        $categoryCodes   = $categories->pluck('code', 'id')->toArray();

        return $schema
            ->columns(2)
            ->components([
                Select::make('category_id')
                    ->label('Категорія')
                    ->options($categoryOptions)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required()
                    ->reactive(),

                TextInput::make('code_suffix')
                    ->label('Код')
                    ->placeholder('напр., CIVIL')
                    ->prefix(fn (Get $get) => strtoupper(($categoryCodes[$get('category_id')] ?? 'CAT') . '_'))
                    ->required()
                    ->minLength(2)
                    ->reactive()
                    ->afterStateUpdated(function (string|null $state, Set $set) {
                        $clean = strtoupper(str_replace(' ', '_', (string) $state));
                        $clean = preg_replace('/[^A-Z0-9_]/', '', $clean);
                        $set('code_suffix', $clean);
                    })
                    ->rules([
                        'regex:/^[A-Z0-9_]+$/',
                        function (Get $get) use ($categoryCodes) {
                            return Rule::unique('insurance_products', 'code')
                                ->where(fn ($q) => $q->where('code',
                                    strtoupper(($categoryCodes[$get('category_id')] ?? 'CAT') . '_' . (string) $get('code_suffix'))
                                ))
                                ->ignore(optional(InsuranceProduct::find($get('id')))->id);
                        },
                    ])
                    ->validationMessages([
                        'required' => 'Вкажіть код (суфікс).',
                        'min'      => 'Код має містити щонайменше 2 символи.',
                        'regex'    => 'Дозволені лише латинські літери, цифри та підкреслення.',
                        'unique'   => 'Такий код вже використовується.',
                    ])
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, Set $set, $record) use ($categoryCodes) {
                        if (! $record) return;
                        $catPrefix = $categoryCodes[$record->category_id] ?? null;
                        if (! $catPrefix || ! is_string($record->code)) return;

                        $expected = strtoupper($catPrefix . '_');
                        $current  = strtoupper($record->code);
                        $suffix   = Str::startsWith($current, $expected)
                            ? substr($current, strlen($expected))
                            : preg_replace('/[^A-Z0-9_]/', '', $current);

                        $set('code_suffix', $suffix);
                    }),

                Hidden::make('code')
                    ->dehydrateStateUsing(function (Get $get) use ($categoryCodes) {
                        $prefix = strtoupper(($categoryCodes[$get('category_id')] ?? 'CAT') . '_');
                        $suffix = strtoupper((string) $get('code_suffix'));
                        $suffix = str_replace(' ', '_', $suffix);
                        $suffix = preg_replace('/[^A-Z0-9_]/', '', $suffix);
                        return $prefix . $suffix;
                    }),

                TextInput::make('name')
                    ->label('Назва')
                    ->required()
                    ->minLength(5)
                    ->validationMessages([
                        'required' => 'Вкажіть назву продукту.',
                        'min'      => 'Назва має містити щонайменше 5 символів.',
                    ])
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Опис')
                    ->required()
                    ->minLength(5)
                    ->validationMessages([
                        'required' => 'Додайте опис.',
                        'min'      => 'Опис має містити щонайменше 5 символів.',
                    ])
                    ->columnSpanFull(),

                Toggle::make('sales_enabled')
                    ->label('Дія')
                    ->required(),
            ]);
    }
}
