<?php

namespace App\Filament\Resources\ClaimNotes\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
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
                        modifyQueryUsing: function ($query) {
                            /** @var User|null $user */
                            $user = Auth::user();

                            if ($user instanceof User && $user->isManager()) {
                                $query->where('reported_by_id', $user->id);
                            }
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

                Select::make('user_id')
                    ->label('Менеджер')
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
                            ->where('is_active', true)
                            ->whereIn('role', ['manager', 'supervisor', 'admin'])
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->default(fn () => Auth::id())
                    ->disabled(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $user instanceof User && $user->isManager();
                    })
                    ->dehydrated(true)
                    ->required()
                    ->columnSpan(1),

                Textarea::make('note')
                    ->label('Нотатка')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }
}