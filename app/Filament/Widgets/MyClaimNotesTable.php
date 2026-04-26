<?php

namespace App\Filament\Widgets;

use App\Models\ClaimNote;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MyClaimNotesTable extends BaseWidget
{
    protected static ?string $heading = 'Останні нотатки по моїх страхових випадках';

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
                ClaimNote::query()
                    ->with(['claim.policy', 'user'])
                    ->whereHas('claim.policy', function (Builder $query) use ($user) {
                        $query->where('agent_id', $user?->id);
                    })
                    ->latest()
            )
            ->defaultPaginationPageOption(10)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('visibility')
                    ->label('Тип')
                    ->badge()
                    ->color(fn ($state) => $state === 'внутрішня' ? 'warning' : 'info'),

                Tables\Columns\TextColumn::make('claim.claim_number')
                    ->label('Заява')
                    ->placeholder('—')
                    ->url(fn (ClaimNote $record) => $record->claim ? "/admin/claims/{$record->claim->id}/edit" : null)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Автор')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('note')
                    ->label('Нотатка')
                    ->limit(100)
                    ->wrap(),
            ]);
    }
}