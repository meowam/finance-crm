<?php

namespace App\Filament\Widgets;

use App\Models\LeadRequest;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class MyNewLeadsTable extends BaseWidget
{
    protected static ?string $heading = 'Мої нові вхідні заявки';

    protected static ?int $sort = 40;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && $user->isManager();
    }

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $table
            ->query(
                LeadRequest::query()
                    ->where('assigned_user_id', $user?->id)
                    ->whereIn('status', ['new', 'in_progress'])
                    ->latest()
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_label')
                    ->label('Вхідна заявка')
                    ->state(fn (LeadRequest $record) => $record->display_label),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон'),

                Tables\Columns\TextColumn::make('interest')
                    ->label('Інтерес')
                    ->limit(60)
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'new' => 'Нова',
                        'in_progress' => 'Опрацьовується',
                        default => (string) $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'new' => 'warning',
                        'in_progress' => 'info',
                        default => 'gray',
                    }),
            ]);
    }
}