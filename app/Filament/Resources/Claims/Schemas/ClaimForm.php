<?php
namespace App\Filament\Resources\Claims\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('claim_number')
                    ->label('Номер заявки')
                    ->readOnly()
                    ->dehydrated(false)
                    ->placeholder('Буде згенеровано під час збереження.')
                    ->columnSpan(1),

                DateTimePicker::make('reported_at')
                    ->label('Дата звернення')
                    ->readOnly()
                    ->dehydrated(false)
                    ->native(false)
                    ->timezone(null)
                    ->seconds(true)
                    ->placeholder('Буде встановлено під час збереження.')
                    ->columnSpan(1),

                Select::make('policy_id')
                    ->label('Номер поліса')
                    ->placeholder('Оберіть поліс...')
                    ->relationship(
                        name: 'policy',
                        titleAttribute: 'policy_number',
                        modifyQueryUsing: fn($query) => $query->where('status', 'active') 
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rules(['required', 'exists:policies,id'])
                    ->columnSpan(2)
                    ->validationMessages([
                        'required' => 'Оберіть номер поліса.',
                        'exists'   => 'Обраний поліс не знайдено.',
                    ]),

                TextInput::make('reported_by_name')
                    ->label('Менеджер')
                    ->readOnly()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (TextInput $component, $state, $record) {
                        $name = $record
                            ? optional($record->reportedBy)->name
                            : optional(Auth::user())->name;     
                        $component->state($name);
                    })
                    ->default(fn() => optional(Auth::user())->name)
                    ->columnSpan(1),

                Hidden::make('reported_by_id')
                    ->default(fn() => Auth::id())
                    ->dehydrated(true)
                    ->rules(['exists:users,id']),

                Select::make('status')
                    ->label('Статус')
                    ->options([
                        'на розгляді' => 'На розгляді',
                        'схвалено'    => 'Схвалено',
                        'виплачено'   => 'Виплачено',
                        'відхилено'   => 'Відхилено',
                    ])
                    ->native(false)
                    ->required()
                    ->rules([Rule::in(['на розгляді', 'схвалено', 'виплачено', 'відхилено'])])
                    ->default('на розгляді')
                    ->columnSpan(1)
                    ->validationMessages([
                        'required' => 'Оберіть статус.',
                        'in'       => 'Недопустиме значення статусу.',
                    ]),

                DatePicker::make('loss_occurred_at')
                    ->label('Дата страхового випадку')
                    ->placeholder('дд.мм.рррр')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->format('Y-m-d')
                    ->timezone(null)
                    ->closeOnDateSelection()
                    ->required()
                    ->minDate(fn() => now()->subYears(5)->startOfDay())
                    ->maxDate(fn() => now()->endOfDay())
                    ->rules(function () {
                        $max = now()->toDateString();
                        $min = now()->subYears(5)->toDateString();
                        return ["before_or_equal:$max", "after_or_equal:$min"];
                    })
                    ->columnSpan(1)
                    ->validationMessages([
                        'required'        => 'Вкажіть дату страхового випадку.',
                        'before_or_equal' => 'Дата не може бути пізніше за сьогодні.',
                        'after_or_equal'  => 'Дата не може бути ранішою, ніж ' . now()->subYears(5)->format('d.m.Y') . '.',
                    ]),

                TextInput::make('loss_location')
                    ->label('Місце події')
                    ->placeholder('м. Київ, вул. Хрещатик, 1')
                    ->required()
                    ->maxLength(255)
                    ->rules(['required', 'string', 'max:255'])
                    ->columnSpan(1)
                    ->validationMessages([
                        'required' => 'Вкажіть місце події.',
                        'max'      => 'Місце події повинно містити не більше 255 символів.',
                    ]),

                TextInput::make('cause')
                    ->label('Причина')
                    ->placeholder('Напр., ДТП, Затоплення, Крадіжка...')
                    ->required()
                    ->maxLength(255)
                    ->rules(['required', 'string', 'max:255'])
                    ->columnSpan(2)
                    ->validationMessages([
                        'required' => 'Вкажіть причину.',
                        'max'      => 'Причина повинна містити не більше 255 символів.',
                    ]),

                Textarea::make('description')
                    ->label('Детальний опис події')
                    ->placeholder('Опишіть обставини події...')
                    ->maxLength(2000)
                    ->rules(['nullable', 'string', 'max:2000'])
                    ->columnSpan(2)
                    ->validationMessages([
                        'max' => 'Опис повинен містити не більше 2000 символів.',
                    ]),

                TextInput::make('amount_claimed')
                    ->label('Заявлена сума')
                    ->placeholder('0,00')
                    ->suffix('₴')
                    ->required()
                    ->extraAttributes([
                        'inputmode' => 'decimal',
                        'pattern'   => '[0-9]+([.,][0-9]{0,2})?',
                    ])
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $clean = preg_replace('/[^0-9,.]/', '', (string) $state);
                        $clean = preg_replace('/([.,].*?)[.,]+/', '$1', $clean);
                        $set('amount_claimed', $clean);
                    })
                    ->rules(['regex:/^\d+(?:[.,]\d{0,2})?$/'])
                    ->dehydrateStateUsing(fn($state) => $state !== null ? str_replace(',', '.', $state) : $state)
                    ->columnSpan(1)
                    ->validationMessages([
                        'required' => 'Вкажіть заявлену суму.',
                        'regex'    => 'Допустимі лише цифри, кома або крапка (до 2 знаків після).',
                    ]),

                TextInput::make('amount_reserve')
                    ->label('Резервна сума')
                    ->placeholder('0,00')
                    ->required()
                    ->suffix('₴')
                    ->extraAttributes([
                        'inputmode' => 'decimal',
                        'pattern'   => '[0-9]+([.,][0-9]{0,2})?',
                    ])
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $clean = preg_replace('/[^0-9,.]/', '', (string) $state);
                        $clean = preg_replace('/([.,].*?)[.,]+/', '$1', $clean);
                        $set('amount_reserve', $clean);
                    })
                    ->rules(['regex:/^\d+(?:[.,]\d{0,2})?$/'])
                    ->dehydrateStateUsing(fn($state) => $state !== null ? str_replace(',', '.', $state) : $state)
                    ->columnSpan(1)
                    ->validationMessages([
                        'regex' => 'Допустимі лише цифри, кома або крапка (до 2 знаків після).',
                    ]),

                TextInput::make('amount_paid')
                    ->label('Виплачена сума')
                    ->placeholder('0')
                    ->suffix('₴')
                    ->extraAttributes([
                        'inputmode' => 'decimal',
                        'pattern'   => '[0-9]+([.,][0-9]{0,2})?',
                    ])
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $clean = preg_replace('/[^0-9,.]/', '', (string) $state);
                        $clean = preg_replace('/([.,].*?)[.,]+/', '$1', $clean);
                        $set('amount_paid', $clean);
                    })
                    ->rules(['regex:/^\d+(?:[.,]\d{0,2})?$/'])
                    ->dehydrateStateUsing(fn($state) => $state !== null ? str_replace(',', '.', $state) : $state)
                    ->hiddenOn(CreateRecord::class)
                    ->columnSpan(1)
                    ->validationMessages([
                        'regex' => 'Допустимі лише цифри, кома або крапка (до 2 знаків після).',
                    ]),

                Repeater::make('notes')
                    ->label('Нотатки по заяві')
                    ->addActionLabel('Додати нотатку по заяві')
                    ->relationship('notes')
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->schema([
                        Select::make('visibility')
                            ->label('Видимість')
                            ->options([
                                'внутрішня' => 'Внутрішня',
                                'зовнішня'  => 'Зовнішня',
                            ])
                            ->native(false)
                            ->required()
                            ->rules([Rule::in(['внутрішня', 'зовнішня'])])
                            ->validationMessages([
                                'required' => 'Оберіть видимість нотатки.',
                                'in'       => 'Недопустиме значення видимості.',
                            ]),

                        TextInput::make('user_name')
                            ->label('Менеджер')
                            ->readOnly()
                            ->dehydrated(false)
                            ->default(fn() => optional(User::find(Auth::id()))?->name)
                            ->afterStateHydrated(function ($state, callable $set, $record) {
                                if ($record && method_exists($record, 'user')) {
                                    $set('user_name', optional($record->user)->name);
                                }
                            }),
                        Hidden::make('user_id')
                            ->default(fn() => Auth::id())
                            ->dehydrated(true)
                            ->rules(['required', 'exists:users,id'])
                            ->validationMessages([
                                'exists' => 'Менеджера не знайдено.',
                            ]),

                        Textarea::make('note')
                            ->label('Нотатка')
                            ->placeholder('Додайте коментар...')
                            ->rows(4)
                            ->required()
                            ->rules(['required', 'string'])
                            ->validationMessages([
                                'required' => 'Введіть текст нотатки.',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpan(2),
            ]);
    }
}
