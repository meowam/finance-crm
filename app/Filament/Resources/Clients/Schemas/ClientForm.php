<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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
                    ->live()
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
                    ->label(fn (Get $get) => $get('type') === 'company' ? "Ім'я контактної особи" : "Ім'я")
                    ->required()
                    ->minLength(2)
                    ->maxLength(50)
                    ->rules(["regex:/^[\\p{L}’'\\- ]+$/u"])
                    ->validationMessages([
                        'required' => "Поле є обов'язковим.",
                        'min'      => "Поле повинно містити щонайменше 2 символи.",
                        'max'      => "Поле повинно містити не більше 50 символів.",
                        'regex'    => "Поле повинно складатися лише з літер, пробілів, апострофа або дефіса.",
                    ]),

                TextInput::make('last_name')
                    ->label(fn (Get $get) => $get('type') === 'company' ? 'Прізвище контактної особи' : 'Прізвище')
                    ->required()
                    ->minLength(3)
                    ->maxLength(50)
                    ->rules(["regex:/^[\\p{L}’'\\- ]+$/u"])
                    ->validationMessages([
                        'required' => 'Поле є обовʼязковим.',
                        'min'      => 'Поле повинно містити щонайменше 3 символи.',
                        'max'      => 'Поле повинно містити не більше 50 символів.',
                        'regex'    => 'Поле повинно складатися лише з літер, пробілів, апострофа або дефіса.',
                    ]),

                TextInput::make('middle_name')
                    ->label(fn (Get $get) => $get('type') === 'company' ? 'По батькові контактної особи' : 'По батькові')
                    ->nullable()
                    ->minLength(2)
                    ->maxLength(50)
                    ->rules(['nullable', "regex:/^[\\p{L}’'\\- ]+$/u"])
                    ->validationMessages([
                        'min'   => 'Поле повинно містити щонайменше 2 символи.',
                        'max'   => 'Поле повинно містити не більше 50 символів.',
                        'regex' => 'Поле повинно складатися лише з літер, пробілів, апострофа або дефіса.',
                    ]),

                TextInput::make('company_name')
                    ->label('Назва компанії')
                    ->nullable()
                    ->live()
                    ->required(fn (Get $get) => $get('type') === 'company')
                    ->visible(fn (Get $get) => $get('type') === 'company')
                    ->rules(['nullable', 'string', 'max:150'])
                    ->validationMessages([
                        'required' => 'Вкажіть назву компанії.',
                        'max'      => 'Назва компанії повинна містити не більше 150 символів.',
                    ]),

                TextInput::make('primary_email')
    ->label('Основна ел. пошта')
    ->email()
    ->required()
    ->rules(fn ($record) => [
        'required',
        'email',
        Rule::unique('clients', 'primary_email')->ignore($record?->id),
    ])
    ->validationMessages([
        'required' => 'Вкажіть електронну пошту.',
        'email'    => 'Електронна пошта повинна бути у коректному форматі. Приклад: manager@company.com',
        'unique'   => 'Клієнт із такою електронною поштою вже існує, включно з архівними записами.',
    ]),
                TextInput::make('primary_phone')
    ->label('Основний телефон')
    ->tel()
    ->placeholder('+380671234567')
    ->required()
    ->rules(fn ($record) => [
        "regex:/^\\+380(39|50|63|66|67|68|73|91|92|93|94|95|96|97|98|99)\\d{7}$/",
        Rule::unique('clients', 'primary_phone')->ignore($record?->id),
    ])
    ->validationMessages([
        'required' => 'Вкажіть номер телефону.',
        'regex'    => 'Телефон повинен бути у форматі +380XXXXXXXXX з коректним кодом оператора. Приклад: +380671234567.',
        'unique'   => 'Клієнт із таким номером телефону вже існує, включно з архівними записами.',
    ]),

                TextInput::make('document_number')
    ->label('Номер документа')
    ->placeholder('AA123456')
    ->required(fn (Get $get) => $get('type') === 'individual')
    ->visible(fn (Get $get) => $get('type') === 'individual')
    ->rules(fn ($record) => [
        'nullable',
        'regex:/^[A-Z]{2}\\d{6}$/',
        Rule::unique('clients', 'document_number')->ignore($record?->id),
    ])
    ->validationMessages([
        'required' => 'Вкажіть номер документа.',
        'regex'    => 'Номер документа повинен бути у форматі AA123456: 2 великі латинські літери + 6 цифр.',
        'unique'   => 'Клієнт із таким номером документа вже існує, включно з архівними записами.',
    ]),

                TextInput::make('tax_id')
    ->label(fn (Get $get) => $get('type') === 'company' ? 'ЄДРПОУ / податковий номер' : 'ІПН')
    ->placeholder(fn (Get $get) => $get('type') === 'company' ? '12345678' : '1234567890')
    ->required()
    ->rules(fn (Get $get, $record) => array_filter([
        $get('type') === 'company' ? 'regex:/^\\d{8,10}$/' : 'regex:/^\\d{10}$/',
        Rule::unique('clients', 'tax_id')->ignore($record?->id),
    ]))
    ->validationMessages([
        'required' => 'Вкажіть податковий номер.',
        'regex'    => 'Для фізичної особи потрібно 10 цифр, для компанії - 8 або 10 цифр.',
        'unique'   => 'Клієнт із таким податковим номером уже існує, включно з архівними записами.',
    ]),

                DatePicker::make('date_of_birth')
                    ->label('Дата народження')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->format('Y-m-d')
                    ->timezone(null)
                    ->closeOnDateSelection()
                    ->required(fn (Get $get) => $get('type') === 'individual')
                    ->visible(fn (Get $get) => $get('type') === 'individual')
                    ->rules(function () {
                        $max = Carbon::now()->subYears(18)->toDateString();
                        $min = Carbon::now()->subYears(73)->toDateString();

                        return ["after_or_equal:$min", "before_or_equal:$max"];
                    })
                    ->validationMessages([
                        'required'        => 'Вкажіть дату народження.',
                        'date'            => 'Дата народження повинна бути у форматі дд.мм.рррр.',
                        'after_or_equal'  => 'Дата народження повинна бути не раніше :date.',
                        'before_or_equal' => 'Дата народження повинна бути не пізніше :date.',
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
                        'landing'        => 'Лендінг',
                        'other'          => 'Інше',
                    ])
                    ->native(false)
                    ->required()
                    ->rules([Rule::in(['office', 'online', 'recommendation', 'landing', 'other'])])
                    ->default('office')
                    ->validationMessages([
                        'required' => 'Оберіть канал звернення.',
                    ]),

                Select::make('assigned_user_id')
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
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required()
                    ->rules([
                        Rule::exists('users', 'id')->where(function ($query) {
                            $query
                                ->where('role', 'manager')
                                ->where('is_active', true);
                        }),
                    ])
                    ->default(function () {
                        /** @var User|null $user */
                        $user = Auth::user();

                        if ($user instanceof User && $user->isManager()) {
                            return $user->id;
                        }

                        return User::query()
                            ->where('is_active', true)
                            ->where('role', 'manager')
                            ->orderBy('name')
                            ->value('id');
                    })
                    ->disabled(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $user instanceof User && $user->isManager();
                    })
                    ->dehydrated(true)
                    ->validationMessages([
                        'required' => 'Оберіть менеджера.',
                        'exists'   => 'Можна призначити лише активного менеджера.',
                    ]),

                Textarea::make('notes')
                    ->label('Нотатки')
                    ->columnSpan(2)
                    ->nullable()
                    ->maxLength(2000)
                    ->rules(['nullable', 'string', 'max:2000'])
                    ->validationMessages([
                        'max' => 'Нотатки повинні містити не більше 2000 символів.',
                    ]),
            ]);
    }
}