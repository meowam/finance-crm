<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        $isAdmin = $authUser instanceof User && $authUser->isAdmin();
        $isSupervisor = $authUser instanceof User && $authUser->isSupervisor();

        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label("Ім'я")
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Select::make('role')
                    ->label('Роль')
                    ->options(
                        $isAdmin
                            ? [
                                'admin' => 'Адміністратор',
                                'supervisor' => 'Керівник',
                                'manager' => 'Менеджер',
                            ]
                            : [
                                'manager' => 'Менеджер',
                            ]
                    )
                    ->default('manager')
                    ->required()
                    ->disabled($isSupervisor)
                    ->dehydrated(true),

                Toggle::make('is_active')
                    ->label('Активний')
                    ->default(true)
                    ->inline(false),

                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                    ->rule(Password::min(8))
                    ->same('passwordConfirmation')
                    ->columnSpan(1),

                TextInput::make('passwordConfirmation')
                    ->label('Підтвердження пароля')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false)
                    ->columnSpan(1),

                DateTimePicker::make('last_login_at')
                    ->label('Останній вхід')
                    ->displayFormat('d.m.Y H:i')
                    ->seconds(false)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpan(1),

                DateTimePicker::make('created_at')
                    ->label('Створено')
                    ->displayFormat('d.m.Y H:i')
                    ->seconds(false)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpan(1),

                DateTimePicker::make('updated_at')
                    ->label('Оновлено')
                    ->displayFormat('d.m.Y H:i')
                    ->seconds(false)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpan(1),
            ]);
    }
}