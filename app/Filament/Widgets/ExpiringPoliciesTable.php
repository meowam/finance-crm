<?php
namespace App\Filament\Widgets;

use App\Models\Policy;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class ExpiringPoliciesTable extends BaseWidget
{
    protected static ?string $heading          = 'Поліси, строк дії яких скоро завершується';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = Auth::user();

        $query = Policy::query()
            ->with([
                'client:id,first_name,last_name,middle_name,primary_email',
                'agent:id,name',
            ])
            ->whereBetween('expiration_date', [
                now()->toDateString(),
                now()->addDays(30)->toDateString(),
            ])
            ->orderBy('expiration_date');

        if ($user?->isManager()) {
            $query->where('agent_id', $user->id);
        }

        return $table
            ->query($query)
            ->defaultPaginationPageOption(10)
            ->columns([
                Tables\Columns\TextColumn::make('policy_number')
                    ->label('Номер полісу')
                    ->searchable()
                    ->url(fn(Policy $record): string => "/admin/policies/{$record->id}/edit")
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('client_full_name')
                    ->label('Клієнт')
                    ->state(function (Policy $record): string {
                        $client = $record->client;

                        if (! $client) {
                            return '—';
                        }

                        $parts = array_filter([
                            $client->last_name,
                            $client->first_name,
                            $client->middle_name,
                        ]);

                        return $parts !== [] ? implode(' ', $parts) : ($client->primary_email ?: '—');
                    }),

                Tables\Columns\TextColumn::make('client.primary_email')
                    ->label('Email')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('agent.name')
                    ->label('Менеджер')
                    ->placeholder('—')
                    ->visible(function (): bool {
                        $user = Auth::user();

                        return ! ($user instanceof \App\Models\User  && $user->isManager());
                    }),

                Tables\Columns\TextColumn::make('expiration_date')
                    ->label('Діє до')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_left')
                    ->label('Залишилось днів')
                    ->state(function (Policy $record): int | string {
                        if (! $record->expiration_date) {
                            return '—';
                        }

                        return now()->startOfDay()->diffInDays($record->expiration_date, false);
                    })
                    ->badge()
                    ->color(function (Policy $record): string {
                        if (! $record->expiration_date) {
                            return 'gray';
                        }

                        $daysLeft = now()->startOfDay()->diffInDays($record->expiration_date, false);

                        return match (true) {
                            $daysLeft <= 7  => 'danger',
                            $daysLeft <= 14 => 'warning',
                            default         => 'info',
                        };
                    }),
            ])
            ->emptyStateHeading('Немає полісів, що завершуються найближчим часом');
    }
}
