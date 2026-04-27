<?php

namespace App\Filament\Widgets;

use App\Models\ClaimNote;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentInternalClaimNotesTable extends BaseWidget
{
protected static ?string $heading = 'Останні 25 внутрішніх нотаток по страхових випадках';
    protected static ?int $sort = 999;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && $user->isSupervisor();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClaimNote::query()
                    ->with(['claim.policy.agent', 'user'])
                    ->where('visibility', 'внутрішня')
                    ->latest()
                    ->limit(25)
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('claim.claim_number')
                    ->label('Заява')
                    ->placeholder('—')
                    ->url(fn (ClaimNote $record) => $record->claim ? "/admin/claims/{$record->claim->id}/edit" : null)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('claim.policy.agent.name')
                    ->label('Менеджер полісу')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Автор нотатки')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('note')
                    ->label('Нотатка')
                    ->limit(100)
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('policy_manager_id')
                    ->label('Менеджер полісу')
                    ->options(fn (): array => User::query()
                        ->where('role', 'manager')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        return $query->whereHas('claim.policy', function (Builder $policyQuery) use ($value) {
                            $policyQuery->where('agent_id', $value);
                        });
                    }),

                SelectFilter::make('author_id')
                    ->label('Автор нотатки')
                    ->options(fn (): array => User::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        return $query->where('user_id', $value);
                    }),
            ])
            ->emptyStateHeading('Внутрішніх нотаток поки немає');
    }
}