<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class RecentActivityLogTable extends BaseWidget
{
    protected static ?string $heading = 'Останні дії користувачів';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && $user->isAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ActivityLog::query()
                    ->with('actor')
                    ->latest()
            )
            ->defaultPaginationPageOption(10)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('actor_name')
                    ->label('Користувач')
                    ->placeholder('Система')
                    ->searchable(),

                Tables\Columns\TextColumn::make('actor_role')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'admin' => 'Адмін',
                        'supervisor' => 'Супервайзер',
                        'manager' => 'Менеджер',
                        default => $state ?: '—',
                    }),

                Tables\Columns\TextColumn::make('action_label')
                    ->label('Дія')
                    ->badge(),

                Tables\Columns\TextColumn::make('subject_type_label')
                    ->label('Сутність')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('subject_label')
                    ->label('Запис')
                    ->limit(50)
                    ->placeholder('—'),
            ]);
    }
}