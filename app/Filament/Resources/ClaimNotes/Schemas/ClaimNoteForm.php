<?php

namespace App\Filament\Resources\ClaimNotes\Schemas;

use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClaimNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('claim_id')
                    ->label('Заява')
                    ->relationship(
                        name: 'claim',
                        titleAttribute: 'claim_number',
                        modifyQueryUsing: function (Builder $query) {
                            /** @var User|null $user */
                            $user = Auth::user();

                            if (! $user instanceof User) {
                                $query->whereRaw('1 = 0');

                                return;
                            }

                            if ($user->isAdmin() || $user->isSupervisor()) {
                                return;
                            }

                            if ($user->isManager()) {
                                $query->whereHas('policy', function (Builder $policyQuery) use ($user) {
                                    $policyQuery->where('agent_id', $user->id);
                                });

                                return;
                            }

                            $query->whereRaw('1 = 0');
                        }
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rules(['required', 'exists:claims,id'])
                    ->columnSpan(1),

                Select::make('visibility')
                    ->label('Видимість')
                    ->options([
                        'внутрішня' => 'Внутрішня',
                        'зовнішня'  => 'Зовнішня',
                    ])
                    ->native(false)
                    ->required()
                    ->rules([Rule::in(['внутрішня', 'зовнішня'])])
                    ->default('внутрішня')
                    ->columnSpan(1),

                Hidden::make('user_id')
                    ->default(fn () => Auth::id())
                    ->dehydrated(true)
                    ->rules(['required', 'exists:users,id']),

                Textarea::make('note')
                    ->label('Нотатка')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }
}