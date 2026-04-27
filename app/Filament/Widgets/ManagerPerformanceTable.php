<?php

namespace App\Filament\Widgets;

use App\Models\Claim;
use App\Models\Client;
use App\Models\LeadRequest;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ManagerPerformanceTable extends BaseWidget
{
    protected static ?string $heading = 'Навантаження менеджерів';

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
                User::query()
                    ->where('role', 'manager')
                    ->where('is_active', true)
                    ->orderBy('name')
            )
            ->defaultPaginationPageOption(10)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Менеджер')
                    ->searchable(),

                Tables\Columns\TextColumn::make('open_leads')
                    ->label('Активні заявки')
                    ->state(fn (User $record) => LeadRequest::query()
                        ->where('assigned_user_id', $record->id)
                        ->whereIn('status', ['new', 'in_progress'])
                        ->count()
                    )
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('active_clients')
                    ->label('Активні клієнти')
                    ->state(fn (User $record) => Client::query()
                        ->where('assigned_user_id', $record->id)
                        ->where('status', 'active')
                        ->count()
                    )
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('active_policies')
                    ->label('Активні поліси')
                    ->state(fn (User $record) => Policy::query()
                        ->where('agent_id', $record->id)
                        ->where('status', 'active')
                        ->count()
                    )
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('overdue_payments')
                    ->label('Прострочені оплати')
                    ->state(fn (User $record) => PolicyPayment::query()
                        ->where('status', 'overdue')
                        ->whereHas('policy', fn (Builder $query) => $query->where('agent_id', $record->id))
                        ->count()
                    )
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('refunded_payments')
                    ->label('Повернені оплати')
                    ->state(fn (User $record) => PolicyPayment::query()
                        ->where('status', 'refunded')
                        ->whereHas('policy', fn (Builder $query) => $query->where('agent_id', $record->id))
                        ->count()
                    )
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('claims')
                    ->label('Страхові випадки')
                    ->state(fn (User $record) => Claim::query()
                        ->whereHas('policy', fn (Builder $query) => $query->where('agent_id', $record->id))
                        ->count()
                    )
                    ->badge()
                    ->color('info'),
            ]);
    }
}