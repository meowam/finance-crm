<?php

namespace App\Filament\Widgets;

use App\Models\ProblemRecord;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminProblemRecordsTable extends BaseWidget
{
    protected static ?string $heading = 'Проблемні записи';

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && ($user->isAdmin() || $user->isSupervisor());
    }

    protected function getTableDescription(): ?string
    {
        return 'Активні заявки або клієнти, які потребують перепризначення відповідального менеджера.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getProblemRecordsQuery())
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('problem_type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'lead' => 'Вхідна заявка',
                        'client' => 'Клієнт',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'lead' => 'warning',
                        'client' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('record_label')
                    ->label('Клієнт / заявка')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('manager_name')
                    ->label('Поточний менеджер')
                    ->placeholder('—')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state)
                        ? "{$state} (неактивний)"
                        : 'Не призначено'
                    )
                    ->color('danger'),

                TextColumn::make('problem_label')
                    ->label('Проблема')
                    ->badge()
                    ->color('danger')
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('problem_type')
                    ->label('Тип')
                    ->options([
                        'lead' => 'Вхідна заявка',
                        'client' => 'Клієнт',
                    ]),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Відкрити')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(function ($record): string {
                        return $record->problem_type === 'lead'
                            ? "/admin/lead-requests/{$record->source_id}/edit?problem_reassign=1"
                            : "/admin/clients/{$record->source_id}/edit?problem_reassign=1";
                    }),
            ])
            ->emptyStateHeading('Проблемних записів не знайдено')
            ->emptyStateDescription('Усі активні заявки та клієнти мають активного відповідального менеджера.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    protected function getProblemRecordsQuery(): Builder
    {
        $leadQuery = DB::table('lead_requests')
            ->select([
                DB::raw("CONCAT('lead-', lead_requests.id) as id"),
                DB::raw("'lead' as problem_type"),
                'lead_requests.id as source_id',
                DB::raw("
                    TRIM(CONCAT_WS(' ',
                        lead_requests.last_name,
                        lead_requests.first_name,
                        lead_requests.middle_name
                    )) as record_label
                "),
                'users.name as manager_name',
                DB::raw("'Активна заявка без активного менеджера' as problem_label"),
                'lead_requests.created_at as created_at',
            ])
            ->leftJoin('users', 'users.id', '=', 'lead_requests.assigned_user_id')
            ->whereIn('lead_requests.status', ['new', 'in_progress'])
            ->where(function ($query) {
                $query
                    ->whereNull('lead_requests.assigned_user_id')
                    ->orWhereNull('users.id')
                    ->orWhere('users.role', '!=', 'manager')
                    ->orWhere('users.is_active', false);
            });

        $clientQuery = DB::table('clients')
            ->select([
                DB::raw("CONCAT('client-', clients.id) as id"),
                DB::raw("'client' as problem_type"),
                'clients.id as source_id',
                DB::raw("
                    CASE
                        WHEN clients.type = 'company' AND clients.company_name IS NOT NULL AND clients.company_name != ''
                            THEN clients.company_name
                        ELSE TRIM(CONCAT_WS(' ',
                            clients.last_name,
                            clients.first_name,
                            clients.middle_name
                        ))
                    END as record_label
                "),
                'users.name as manager_name',
                DB::raw("'Активний клієнт без активного менеджера' as problem_label"),
                'clients.created_at as created_at',
            ])
            ->leftJoin('users', 'users.id', '=', 'clients.assigned_user_id')
            ->whereNull('clients.deleted_at')
            ->where('clients.status', 'active')
            ->where(function ($query) {
                $query
                    ->whereNull('clients.assigned_user_id')
                    ->orWhereNull('users.id')
                    ->orWhere('users.role', '!=', 'manager')
                    ->orWhere('users.is_active', false);
            });

        $union = $leadQuery->unionAll($clientQuery);

        return ProblemRecord::query()
            ->fromSub($union, 'problem_records')
            ->select([
                'problem_records.id',
                'problem_records.problem_type',
                'problem_records.source_id',
                'problem_records.record_label',
                'problem_records.manager_name',
                'problem_records.problem_label',
                'problem_records.created_at',
            ])
            ->orderByDesc('problem_records.created_at');
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->id;
    }
}