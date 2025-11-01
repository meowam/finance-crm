<?php
namespace App\Filament\Resources\Users;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Не показываем в навигации
    protected static bool $shouldRegisterNavigation = false;

    public static function getLabel(): string
    {return 'Користувач';}
    public static function getPluralLabel(): string
    {return 'Користувачі';}
    public static function getNavigationLabel(): string
    {return 'Користувачі';}

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label("Ім'я")
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('email')
                    ->label('Email')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('role')
                    ->label('Роль')->default('Менеджер')
                    ->disabled()->columnSpan(2)
                    ->dehydrated(false)
                    ->afterStateHydrated(function (TextInput $component, $state) {
                        $component->state(match ($state) {
                            'admin'      => 'Адміністратор',
                            'supervisor' => 'Керівник',
                            'manager'    => 'Менеджер',
                            default      => (string) $state, // на всякий случай
                        });
                    }),

                // Select::make('role')
                //     ->label('Роль')
                //     ->options([
                //         'admin'      => 'Адміністратор',
                //         'supervisor' => 'Керівник',
                //         'manager'    => 'Менеджер',
                //     ])
                //     ->disabled()
                //     ->dehydrated(false),

                Toggle::make('is_active')
                    ->label('Активний')

                    ->disabled()
                    ->dehydrated(false),

                DateTimePicker::make('last_login_at')
                    ->label('Останній вхід')
                    ->displayFormat('d.m.Y H:i')
                    ->seconds(false)
                    ->disabled()->columnSpan(2)
                    ->dehydrated(false),

                DateTimePicker::make('created_at')
                    ->label('Створено')
                    ->displayFormat('d.m.Y H:i')
                    ->seconds(false)
                    ->disabled()
                    ->dehydrated(false),

                DateTimePicker::make('updated_at')
                    ->label('Оновлено')
                    ->displayFormat('d.m.Y H:i')
                    ->seconds(false)
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Таблица не нужна — оставляем пусто
        return $table->columns([]);
    }

    public static function getPages(): array
    {
        // Единственная страница — «просмотр» на базе EditRecord
        return [
            'index' => UserResource\Pages\ListUsers::route('/'),
            'edit'  => UserResource\Pages\EditUser::route('/{record}'),
        ];
    }
}
