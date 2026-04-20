<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    protected static function fieldLabels(): array
    {
        return [
            'name' => 'Ім’я',
            'email' => 'Email',
            'role' => 'Роль',
            'is_active' => 'Активний',

            'type' => 'Тип',
            'status' => 'Статус',
            'first_name' => 'Ім’я',
            'last_name' => 'Прізвище',
            'middle_name' => 'По батькові',
            'company_name' => 'Назва компанії',
            'primary_email' => 'Основний email',
            'primary_phone' => 'Основний телефон',
            'document_number' => 'Номер документа',
            'tax_id' => 'ІПН / ЄДРПОУ',
            'date_of_birth' => 'Дата народження',
            'preferred_contact_method' => 'Бажаний спосіб звʼязку',
            'city' => 'Місто',
            'address_line' => 'Адреса',
            'source' => 'Джерело',
            'assigned_user_id' => 'Менеджер',
            'notes' => 'Нотатки',

            'policy_number' => 'Номер полісу',
            'client_id' => 'Клієнт',
            'insurance_offer_id' => 'Страховий продукт',
            'agent_id' => 'Менеджер',
            'effective_date' => 'Початок дії',
            'expiration_date' => 'Закінчення дії',
            'premium_amount' => 'Сума до оплати',
            'coverage_amount' => 'Сума покриття',
            'payment_frequency' => 'Періодичність оплати',
            'commission_rate' => 'Комісія',
            'payment_due_at' => 'Дедлайн оплати',

            'due_date' => 'Строк оплати',
            'initiated_at' => 'Ініційовано',
            'paid_at' => 'Сплачено',
            'amount' => 'Сума',
            'method' => 'Метод оплати',
            'transaction_reference' => 'Номер транзакції',

            'claim_number' => 'Номер заяви',
            'policy_id' => 'Поліс',
            'reported_by_id' => 'Хто зареєстрував',
            'reported_at' => 'Дата звернення',
            'loss_occurred_at' => 'Дата події',
            'loss_location' => 'Місце події',
            'cause' => 'Причина',
            'amount_claimed' => 'Заявлена сума',
            'amount_reserve' => 'Резервна сума',
            'amount_paid' => 'Виплачена сума',
            'description' => 'Опис',
            'visibility' => 'Видимість',
            'note' => 'Нотатка',
            'user_id' => 'Користувач',
        ];
    }

    protected static function hiddenFields(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'remember_token',
            'password',
        ];
    }

    protected static function formatDateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('d.m.Y H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function resolveClientLabel(mixed $id): string
    {
        $client = Client::find($id);

        if (! $client) {
            return 'Клієнт #' . (string) $id;
        }

        if ($client->type === 'company' && filled($client->company_name)) {
            return $client->company_name;
        }

        return $client->full_name !== '—'
            ? $client->full_name
            : ('Клієнт #' . (string) $id);
    }

    protected static function resolveUserLabel(mixed $id): string
    {
        return User::find($id)?->name ?? ('Користувач #' . (string) $id);
    }

    protected static function resolvePolicyLabel(mixed $id): string
    {
        return Policy::find($id)?->policy_number ?? ('Поліс #' . (string) $id);
    }

    protected static function formatValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (in_array($key, ['date_of_birth', 'effective_date', 'expiration_date', 'payment_due_at', 'due_date', 'loss_occurred_at'], true)) {
            try {
                return Carbon::parse((string) $value)->format('d.m.Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        if (in_array($key, ['reported_at', 'initiated_at', 'paid_at', 'last_login_at'], true)) {
            $formatted = static::formatDateValue($value);

            return $formatted ?? (string) $value;
        }

        return match ($key) {
            'status' => match ((string) $value) {
                'lead' => 'Потенційний',
                'active' => 'Активний',
                'archived' => 'Архівовано',
                'draft' => 'Чернетка',
                'completed' => 'Завершено',
                'canceled' => 'Скасовано',
                'scheduled' => 'Заплановано',
                'paid' => 'Сплачено',
                'overdue' => 'Прострочено',
                'new' => 'Нова',
                'in_progress' => 'В роботі',
                'converted' => 'Конвертовано',
                'rejected' => 'Відхилено',
                'на розгляді' => 'На розгляді',
                'схвалено' => 'Схвалено',
                'виплачено' => 'Виплачено',
                'відхилено' => 'Відхилено',
                default => (string) $value,
            },

            'type' => match ((string) $value) {
                'individual' => 'Фізична особа',
                'company' => 'Компанія',
                default => (string) $value,
            },

            'role' => match ((string) $value) {
                'admin' => 'Адміністратор',
                'supervisor' => 'Керівник відділу',
                'manager' => 'Менеджер',
                default => (string) $value,
            },

            'source' => match ((string) $value) {
                'office' => 'Офіс',
                'online' => 'Онлайн',
                'recommendation' => 'Рекомендація',
                'landing' => 'Лендінг',
                'other' => 'Інше',
                default => (string) $value,
            },

            'preferred_contact_method' => match ((string) $value) {
                'phone' => 'Телефон',
                'email' => 'Email',
                default => (string) $value,
            },

            'method' => match ((string) $value) {
                'no_method' => 'Не вибрано',
                'cash' => 'Готівка',
                'card' => 'Картка',
                'transfer' => 'Переказ',
                default => (string) $value,
            },

            'is_active' => (bool) $value ? 'Так' : 'Ні',

            'policy_id' => static::resolvePolicyLabel($value),
            'client_id' => static::resolveClientLabel($value),
            'reported_by_id', 'assigned_user_id', 'agent_id', 'user_id' => static::resolveUserLabel($value),
            'insurance_offer_id' => 'Пропозиція #' . (string) $value,

            default => is_array($value)
                ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (string) $value,
        };
    }

    protected static function prettifyChanges(null|array|string $data): ?string
    {
        if ($data === null || $data === '' || $data === []) {
            return null;
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $data = $decoded;
            } else {
                return $data;
            }
        }

        if (! is_array($data) || $data === []) {
            return null;
        }

        $labels = static::fieldLabels();
        $hidden = static::hiddenFields();
        $lines = [];

        foreach ($data as $key => $value) {
            if (in_array((string) $key, $hidden, true)) {
                continue;
            }

            $label = $labels[$key] ?? $key;
            $prettyValue = static::formatValue((string) $key, $value);
            $lines[] = "{$label}: {$prettyValue}";
        }

        return $lines === [] ? null : implode(PHP_EOL, $lines);
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('created_at')
                    ->label('Коли')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                TextColumn::make('actor_name')
                    ->label('Хто')
                    ->searchable()
                    ->placeholder('Система'),

                TextColumn::make('actor_role')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'admin' => 'admin',
                        'supervisor' => 'supervisor',
                        'manager' => 'manager',
                        null => 'system',
                        default => (string) $state,
                    }),

                TextColumn::make('action_label')
                    ->label('Дія')
                    ->badge()
                    ->state(fn (ActivityLog $record) => $record->action_label),

                TextColumn::make('subject_type_label')
                    ->label('Сутність')
                    ->searchable(),

                TextColumn::make('subject_label')
                    ->label('Об’єкт')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('description')
                    ->label('Опис')
                    ->wrap()
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Дія')
                    ->options([
                        'created' => 'Створення',
                        'updated' => 'Оновлення',
                        'deleted' => 'Видалення',
                    ]),

                SelectFilter::make('actor_role')
                    ->label('Роль')
                    ->options([
                        'admin' => 'admin',
                        'supervisor' => 'supervisor',
                        'manager' => 'manager',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Деталі')
                    ->modalHeading('Перегляд запису активності')
                    ->fillForm(fn (ActivityLog $record): array => [
                        'description' => $record->description,
                        'before_pretty' => self::prettifyChanges($record->before),
                        'after_pretty' => self::prettifyChanges($record->after),
                    ])
                    ->form([
                        Textarea::make('description')
                            ->label('Опис')
                            ->readOnly()
                            ->rows(2),

                        Textarea::make('before_pretty')
                            ->label('Було')
                            ->readOnly()
                            ->rows(12),

                        Textarea::make('after_pretty')
                            ->label('Стало')
                            ->readOnly()
                            ->rows(12),
                    ]),
            ])
            ->toolbarActions([]);
    }
}