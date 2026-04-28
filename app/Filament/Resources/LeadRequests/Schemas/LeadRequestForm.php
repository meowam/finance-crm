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
    protected static function isProblemReassignMode(): bool
    {
        return request()->boolean('problem_reassign');
    }

    protected static function isActiveManager(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return User::query()
            ->whereKey($userId)
            ->where('role', 'manager')
            ->where('is_active', true)
            ->exists();
    }

    protected static function interestOptions(): array
    {
        return [
            'Автострахування' => 'Автострахування',
            'Страхування майна' => 'Страхування майна',
            'Здоров’я та життя' => 'Здоров’я та життя',
            'Страхування подорожей' => 'Страхування подорожей',
            'Корпоративні програми' => 'Корпоративні програми',
            'Індивідуальне рішення' => 'Індивідуальне рішення',
            'Інше' => 'Інше',
        ];
    }

    protected static function sourceOptions(): array
    {
        return [
            'office' => 'Офіс',
            'landing' => 'Лендінг',
            'recommendation' => 'Рекомендація',
        ];
    }

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
                    ->rules([Rule::in(['individual', 'company'])])
                    ->disabled(fn () => static::isProblemReassignMode()),

                Select::make('status')
                    ->label('Статус')
                    ->options(function ($record): array {
                        $options = [
                            'new' => 'Нова',
                            'in_progress' => 'Опрацьовується',
                            'rejected' => 'Відхилено',
                        ];

                        $isConverted = $record
                            && (string) $record->status === 'converted'
                            && filled($record->converted_client_id);

                        if ($isConverted) {
                            $options['converted'] = 'Конвертовано';
                        }

                        return $options;
                    })
                    ->native(false)
                    ->default('new')
                    ->required()
                    ->disabled(function (?string $operation, $record): bool {
                        if ($operation === 'create' || static::isProblemReassignMode()) {
                            return true;
                        }

                        return $record
                            && (string) $record->status === 'converted'
                            && filled($record->converted_client_id);
                    })
                    ->dehydrated(true)
                    ->rules(function ($record): array {
                        $allowedStatuses = ['new', 'in_progress', 'rejected'];

                        if ($record && (string) $record->status === 'converted' && filled($record->converted_client_id)) {
                            $allowedStatuses[] = 'converted';
                        }

                        return [
                            'required',
                            Rule::in($allowedStatuses),
                        ];
                    }),

                TextInput::make('first_name')
                    ->label(fn (Get $get) => $get('type') === 'company' ? "Ім'я контактної особи" : "Ім'я")
                    ->required()
                    ->maxLength(50)
                    ->disabled(fn () => static::isProblemReassignMode()),

                TextInput::make('last_name')
                    ->label(fn (Get $get) => $get('type') === 'company' ? 'Прізвище контактної особи' : 'Прізвище')
                    ->required()
                    ->maxLength(50)
                    ->disabled(fn () => static::isProblemReassignMode()),

                TextInput::make('middle_name')
                    ->label(fn (Get $get) => $get('type') === 'company' ? 'По батькові контактної особи' : 'По батькові')
                    ->maxLength(50)
                    ->disabled(fn () => static::isProblemReassignMode()),

                TextInput::make('company_name')
                    ->label('Назва компанії')
                    ->required(fn (Get $get) => $get('type') === 'company')
                    ->visible(fn (Get $get) => $get('type') === 'company')
                    ->maxLength(150)
                    ->disabled(fn () => static::isProblemReassignMode()),

                TextInput::make('phone')
                    ->label('Телефон')
                    ->tel()
                    ->required()
                    ->placeholder('+380671234567')
                    ->rules(["regex:/^\\+380(39|50|63|66|67|68|73|91|92|93|94|95|96|97|98|99)\\d{7}$/"])
                    ->disabled(fn () => static::isProblemReassignMode()),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(150)
                    ->disabled(fn () => static::isProblemReassignMode()),

                Select::make('interest')
                    ->label('Цікавить продукт / послуга')
                    ->placeholder('Оберіть напрям звернення')
                    ->options(static::interestOptions())
                    ->native(false)
                    ->searchable()
                    ->required()
                    ->rules([
                        'required',
                        Rule::in(array_keys(static::interestOptions())),
                    ])
                    ->columnSpan(2)
                    ->disabled(fn () => static::isProblemReassignMode()),

                Select::make('source')
                    ->label('Джерело')
                    ->options(static::sourceOptions())
                    ->native(false)
                    ->default('office')
                    ->required()
                    ->rules([
                        'required',
                        Rule::in(array_keys(static::sourceOptions())),
                    ])
                    ->disabled(fn () => static::isProblemReassignMode()),

                Select::make('assigned_user_id')
                    ->label('Менеджер')
                    ->placeholder('Оберіть менеджера')
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

                        if (static::isProblemReassignMode()) {
                            return null;
                        }

                        return app(ManagerAssignmentService::class)->resolveLeastBusyManagerId();
                    })
                    ->afterStateHydrated(function ($state, callable $set): void {
                        if (! static::isProblemReassignMode()) {
                            return;
                        }

                        $managerId = $state ? (int) $state : null;

                        if (! static::isActiveManager($managerId)) {
                            $set('assigned_user_id', null);
                        }
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
                    ->columnSpan(2)
                    ->disabled(fn () => static::isProblemReassignMode()),
            ]);
    }
}