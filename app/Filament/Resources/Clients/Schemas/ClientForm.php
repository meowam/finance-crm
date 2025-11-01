<?php
namespace App\Filament\Resources\Clients\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('type')
                    ->label('Тип')
                    ->options([
                        'individual' => 'Фізична особа',
                        'company'    => 'Компанія',
                    ])
                    ->required()
                    ->native(false)
                    ->rules([Rule::in(['individual', 'company'])])
                    ->default('individual')
                    ->validationMessages([
                        'required' => 'Оберіть тип клієнта.',
                    ]),

                TextInput::make('status')
                    ->label('Статус')
                    ->readOnly()
                    ->required()
                    ->hiddenOn(EditRecord::class)
                    ->dehydrateStateUsing(fn ($state) => 'lead')
                    ->default('Потенційний'),

                Select::make('status')
                    ->label('Статус')
                    ->options([
                        'lead'     => 'Потенційний',
                        'active'   => 'Активний',
                        'archived' => 'Архівовано',
                    ])
                    ->required()
                    ->native(false)
                    ->rules([Rule::in(['lead', 'active', 'archived'])])
                    ->hiddenOn(CreateRecord::class)
                    ->default('active')
                    ->validationMessages([
                        'required' => 'Поле «Статус» є обовʼязковим.',
                    ]),

                TextInput::make('first_name')
                    ->label("Ім'я")
                    ->required()
                    ->minLength(2)
                    ->maxLength(50)
                    ->rules(["regex:/^[\p{L}’'\- ]+$/u"])
                    ->default('Олена')
                    ->validationMessages([
                        'required' => "Поле «Ім'я» є обов'язковим.",
                        'min'      => "Ім'я повинно містити щонайменше 2 символи.",
                        'max'      => "Ім'я повинно містити не більше 50 символів.",
                        'regex'    => "Ім'я повинно складатися лише з літер, пробілів, апострофа або дефіса. Приклад: «Олена», «Анна-Марія».",
                    ]),

                TextInput::make('last_name')
                    ->label('Прізвище')
                    ->required()
                    ->minLength(3)
                    ->maxLength(50)
                    ->rules(["regex:/^[\p{L}’'\- ]+$/u"])
                    ->default('Коваль')
                    ->validationMessages([
                        'required' => 'Поле «Прізвище» є обовʼязковим.',
                        'min'      => 'Прізвище повинно містити щонайменше 3 символи.',
                        'max'      => 'Прізвище повинно містити не більше 50 символів.',
                        'regex'    => 'Прізвище повинно складатися лише з літер, пробілів, апострофа або дефіса. Приклад: «Коваль», «Шевченко-Петренко».',
                    ]),

                TextInput::make('middle_name')
                    ->label('По батькові')
                    ->nullable()
                    ->minLength(2)
                    ->maxLength(50)
                    ->rules(['nullable', "regex:/^[\p{L}’'\- ]+$/u"])
                    ->validationMessages([
                        'min'   => 'По батькові повинно містити щонайменше 2 символи.',
                        'max'   => 'По батькові повинно містити не більше 50 символів.',
                        'regex' => 'По батькові повинно складатися лише з літер, пробілів, апострофа або дефіса. Приклад: «Іванівна».',
                    ]),

                TextInput::make('company_name')
                    ->label('Назва компанії')
                    ->nullable()
                    ->rules(['nullable', 'required_if:type,company', 'string', 'max:150'])
                    ->default('ТОВ «Ромашка»')
                    ->validationMessages([
                        'required_if' => 'Вкажіть назву компанії.',
                        'max'         => 'Назва компанії повинна містити не більше 150 символів.',
                    ]),

                TextInput::make('primary_email')
                    ->label('Основна ел. пошта')
                    ->email()
                    ->required()
                    ->rules(['email:rfc,dns'])
                    ->default('test@gmail.com')
                    ->validationMessages([
                        'required' => 'Вкажіть електронну пошту.',
                        'email'    => 'Електронна пошта повинна бути у коректному форматі. Приклад: manager@company.com',
                    ]),

                TextInput::make('primary_phone')
                    ->label('Основний телефон')
                    ->tel()
                    ->placeholder('+380671234567')
                    ->required()
                    ->rules(["regex:/^\+380(39|50|63|66|67|68|73|91|92|93|94|95|96|97|98|99)\d{7}$/"])
                    ->default('+380671234567')
                    ->validationMessages([
                        'required' => 'Вкажіть номер телефону.',
                        'regex'    => 'Телефон повинен бути у форматі +380XXXXXXXXX з коректним кодом оператора. Приклад: +380671234567.',
                    ]),

                TextInput::make('document_number')
                    ->label('Номер документа')
                    ->placeholder('AA123456')
                    ->required()
                    ->rules(['regex:/^[A-Z]{2}\d{6}$/'])
                    ->default('AA123456')
                    ->validationMessages([
                        'required' => 'Вкажіть номер документа.',
                        'regex'    => 'Номер документа повинен бути у форматі AA123456: 2 великі латинські літери + 6 цифр (приклад: KB905423).',
                    ]),

                TextInput::make('tax_id')
                    ->label('ІПН / ЄДРПОУ')
                    ->placeholder('6519864773')
                    ->required()
                    ->rules(['regex:/^\d{10}$/'])
                    ->default('1234567890')
                    ->validationMessages([
                        'required' => 'Вкажіть ІПН / ЄДРПОУ.',
                        'regex'    => 'ІПН / ЄДРПОУ повинен містити рівно 10 цифр. Приклад: 6519864773.',
                    ]),

                DatePicker::make('date_of_birth')
                    ->label('Дата народження')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->format('Y-m-d')
                    ->timezone(null)
                    ->closeOnDateSelection()
                    ->required()
                    ->rules(function () {
                        $max = Carbon::now()->subYears(18)->toDateString();
                        $min = Carbon::now()->subYears(73)->toDateString();
                        return ["after_or_equal:$min", "before_or_equal:$max"];
                    })
                    ->default(Carbon::now()->subYears(30)->toDateString())
                    ->validationMessages([
                        'required'        => 'Вкажіть дату народження.',
                        'date'            => 'Дата народження повинна бути у форматі дд.мм.рррр (приклад: 31.10.1995).',
                        'after_or_equal'  => 'Дата народження повинна бути не раніше :date (обмеження 73 роки).',
                        'before_or_equal' => 'Дата народження повинна бути не пізніше :date (мінімум 18 років).',
                    ]),

                Select::make('preferred_contact_method')
                    ->label('Бажаний спосіб звʼязку')
                    ->options([
                        'phone' => 'Телефон',
                        'email' => 'Email',
                    ])
                    ->native(false)
                    ->required()
                    ->rules([Rule::in(['phone', 'email'])])
                    ->default('phone')
                    ->validationMessages([
                        'required' => 'Оберіть бажаний спосіб звʼязку.',
                        'in'       => 'Бажаний спосіб звʼязку повинен бути: «Телефон» або «Email».',
                    ]),

                TextInput::make('city')
                    ->label('Місто')
                    ->placeholder('Київ')
                    ->required()
                    ->maxLength(50)
                    ->rules(['string', 'max:50'])
                    ->validationMessages([
                        'required' => 'Вкажіть місто.',
                        'max'      => 'Назва міста повинна містити не більше 50 символів.',
                    ]),

                TextInput::make('address_line')
                    ->label('Адреса')
                    ->placeholder('вул. Хрещатик, 1, кв. 10')
                    ->required()
                    ->maxLength(255)
                    ->rules(['string', 'max:255'])
                    ->default('вул. Хрещатик, 1, кв. 10')
                    ->validationMessages([
                        'required' => 'Вкажіть адресу.',
                        'max'      => 'Адреса повинна містити не більше 255 символів.',
                    ]),

                Select::make('source')
                    ->label('Канал звернення')
                    ->options([
                        'office'         => 'Офіс',
                        'online'         => 'Онлайн',
                        'recommendation' => 'Рекомендація',
                    ])
                    ->native(false)
                    ->required()
                    ->rules([Rule::in(['office', 'online', 'recommendation'])])
                    ->default('office')
                    ->validationMessages([
                        'required' => 'Оберіть канал звернення.',
                    ]),

                Select::make('assigned_user_id')
                    ->label('Менеджер')
                    ->options(fn () => User::query()
                        ->where('is_active', true)
                        ->whereIn('role', ['manager'])
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required()
                    ->rules(['exists:users,id'])
                    ->default(fn () => User::query()
                        ->where('is_active', true)
                        ->whereIn('role', ['manager'])
                        ->orderBy('name')
                        ->value('id')
                    )
                    ->validationMessages([
                        'required' => 'Оберіть менеджера.',
                        'exists'   => 'Обраного менеджера не знайдено.',
                    ]),

                Textarea::make('notes')
                    ->label('Нотатки')
                    ->columnSpan(2)
                    ->nullable()
                    ->maxLength(2000)
                    ->rules(['nullable', 'string', 'max:2000'])
                    ->default('Тестова нотатка')
                    ->validationMessages([
                        'max' => 'Нотатки повинні містити не більше 2000 символів.',
                    ]),
            ]);
    }
}
