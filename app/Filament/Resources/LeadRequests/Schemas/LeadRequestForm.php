<?php

namespace App\Filament\Resources\LeadRequests\Schemas;

use App\Models\User;
use App\Services\Assignments\ManagerAssignmentService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LeadRequestForm
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
                        'company' => 'Компанія',
                    ])
                    ->native(false)
                    ->default('individual')
                    ->live()
                    ->required()
                    ->rules([Rule::in(['individual', 'company'])]),

                Select::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Нова',
                        'in_progress' => 'Опрацьовується',
                        'converted' => 'Конвертовано',
                        'rejected' => 'Відхилено',
                    ])
                    ->native(false)
                    ->default('new')
                    ->required()
                    ->disabled(fn (?string $operation) => $operation === 'create')
                    ->dehydrated(true),

                TextInput::make('first_name')
                    ->label(fn (Get $get) => $get('type') === 'company' ? "Ім'я контактної особи" : "Ім'я")
                    ->required()
                    ->maxLength(50),

                TextInput::make('last_name')
                    ->label(fn (Get $get) => $get('type') === 'company' ? 'Прізвище контактної особи' : 'Прізвище')
                    ->required()
                    ->maxLength(50),

                TextInput::make('middle_name')
                    ->label(fn (Get $get) => $get('type') === 'company' ? 'По батькові контактної особи' : 'По батькові')
                    ->maxLength(50),

                TextInput::make('company_name')
                    ->label('Назва компанії')
                    ->required(fn (Get $get) => $get('type') === 'company')
                    ->visible(fn (Get $get) => $get('type') === 'company')
                    ->maxLength(150),

                TextInput::make('phone')
                    ->label('Телефон')
                    ->tel()
                    ->required()
                    ->placeholder('+380671234567')
                    ->rules(["regex:/^\\+380(39|50|63|66|67|68|73|91|92|93|94|95|96|97|98|99)\\d{7}$/"]),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(150),

                TextInput::make('interest')
                    ->label('Цікавить продукт / послуга')
                    ->placeholder('Наприклад: автострахування, медичне страхування...')
                    ->maxLength(150)
                    ->columnSpan(2),

                Select::make('source')
                    ->label('Джерело')
                    ->options([
                        'office' => 'Офіс',
                        'online' => 'Онлайн',
                        'recommendation' => 'Рекомендація',
                        'landing' => 'Лендінг',
                        'other' => 'Інше',
                    ])
                    ->native(false)
                    ->default('online')
                    ->required(),

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
                            ->where('role', 'manager')
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->default(function () {
                        /** @var User|null $user */
                        $user = Auth::user();

                        if ($user instanceof User && $user->isManager()) {
                            return $user->id;
                        }

                        return app(ManagerAssignmentService::class)->resolveLeastBusyManagerId();
                    })
                    ->disabled(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $user instanceof User && $user->isManager();
                    })
                    ->dehydrated(true)
                    ->required()
                    ->rules([
                        Rule::exists('users', 'id')->where(function ($query) {
                            $query
                                ->where('role', 'manager')
                                ->where('is_active', true);
                        }),
                    ])
                    ->validationMessages([
                        'required' => 'Оберіть менеджера.',
                        'exists' => 'Можна призначити лише активного менеджера.',
                    ]),

                TextInput::make('converted_client_id')
                    ->label('Створений клієнт')
                    ->visibleOn(EditRecord::class)
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn ($state, $record) => $record?->convertedClient?->display_label ?? '—'),

                Textarea::make('comment')
                    ->label('Коментар')
                    ->rows(4)
                    ->columnSpan(2),
            ]);
    }
}