<?php
namespace App\Filament\Resources\InsuranceCompanies\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InsuranceCompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([

                TextInput::make('name')
                    ->label('Назва компанії')
                    ->placeholder('ТАС')
                    ->required()
                    ->minLength(2)
                    ->maxLength(150)
                    ->validationMessages([
                        'required' => 'Вкажіть назву компанії.',
                        'min'      => 'Назва має містити щонайменше 2 символи.',
                        'max'      => 'Назва має містити не більше 150 символів.',
                    ]),

                TextInput::make('license_number')
                    ->label('Номер ліцензії')
                    ->placeholder('UA-TAS-010')
                    ->required()
                    ->rule('regex:/^[A-Z]{2}-[A-Z]{2,}-\d{3}$/i')
                    ->dehydrateStateUsing(fn($state) => filled($state) ? Str::upper(trim($state)) : null)
                    ->validationMessages([
                        'required' => 'Вкажіть номер ліцензії.',
                        'regex'    => 'Формат повинен бути на кшталт UA-TAS-010 (латиниця, тире, 3 цифри).',
                    ]),

                Select::make('country')
                    ->label('Країна')
                    ->required()
                    ->options(fn() => collect(config('countries.uk', []))->sort()->toArray())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->default('Україна')
                    ->validationMessages([
                        'required' => 'Оберіть країну.',
                    ]),

                TextInput::make('contact_email')
                    ->label('Електронна пошта')
                    ->placeholder('info@gmail.com')
                    ->email()
                    ->required()
                    ->rules(['email:rfc,dns'])
                    ->validationMessages([
                        'required' => 'Вкажіть електронну пошту.',
                        'email'    => 'Ел. пошта має бути у коректному форматі (напр., info@gmail.com).',
                    ]),

                TextInput::make('contact_phone')
                    ->label('Телефон')
                    ->tel()
                    ->placeholder('+380504123456')
                    ->required()
                    ->rules(["regex:/^\+380(39|44|50|63|66|67|68|73|91|92|93|94|95|96|97|98|99)\d{7}$/"])
                    ->validationMessages([
                        'required' => 'Вкажіть номер телефону.',
                        'regex'    => 'Телефон має бути у форматі +380XXXXXXXXX з коректним кодом (напр., +380671234567).',
                    ]),

                TextInput::make('website')
                    ->label('Вебсайт')
                    ->placeholder('https://sgtas.ua')
                    ->url()
                    ->required()
                    ->validationMessages([
                        'required' => 'Вкажіть вебсайт.',
                        'url'      => 'Вебсайт має бути коректною URL-адресою (напр., https://sgtas.ua).',
                    ]),

                Placeholder::make('logo_preview')
                    ->label('Поточний логотип')
                    ->hidden(function ($record) {
                        if (! $record?->logo_path) {
                            return true;
                        }

                        $path = ltrim(str_replace(['storage/', 'public/'], '', $record->logo_path), '/');

                        return ! Storage::disk('public')->exists($path);
                    })
                    ->content(function ($record) {
                        $path = ltrim(str_replace(['storage/', 'public/'], '', $record->logo_path), '/');
                        $url  = asset('storage/' . $path);

                        return new HtmlString(
                            '<a href="' . e($url) . '" target="_blank" rel="noopener" style="display:inline-block;line-height:0;">
                <img src="' . e($url) . '"
                     alt="logo"
                     style="max-width:240px;max-height:160px;border-radius:8px;display:block;" />
             </a>'
                        );
                    })
                    ->dehydrated(false)
                    ->visibleOn(EditRecord::class)
                    ->columnSpanFull(),

                FileUpload::make('logo_path')
                    ->label('Логотип')
                    ->image()
                    ->disk('public')
                    ->directory('logos')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->previewable(false)
                    ->imageEditor(false)
                    ->required()
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
                    ->validationMessages([
                        'required' => 'Додайте логотип.',
                        'image'    => 'Файл має бути зображенням.',
                        'max'      => 'Розмір логотипу не повинен перевищувати 2 МБ.',
                    ])
                    ->columnSpan(2)
                    ->openable(),
            ]);
    }
}
