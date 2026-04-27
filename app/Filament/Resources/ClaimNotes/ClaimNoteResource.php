<?php

namespace App\Filament\Resources\ClaimNotes;

use App\Filament\Resources\ClaimNotes\Pages\CreateClaimNote;
use App\Filament\Resources\ClaimNotes\Pages\EditClaimNote;
use App\Filament\Resources\ClaimNotes\Pages\ListClaimNotes;
use App\Filament\Resources\ClaimNotes\Schemas\ClaimNoteForm;
use App\Filament\Resources\ClaimNotes\Tables\ClaimNotesTable;
use App\Models\ClaimNote;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ClaimNoteResource extends Resource
{
    protected static ?string $model = ClaimNote::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Нотатки';
    protected static ?string $modelLabel = 'Нотатка';
    protected static ?string $pluralModelLabel = 'Нотатки';

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && $user->can('viewAny', ClaimNote::class);
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && $user->can('create', ClaimNote::class);
    }

    public static function form(Schema $schema): Schema
    {
        return ClaimNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClaimNotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();

        $query = parent::getEloquentQuery()->with(['claim.policy', 'user']);

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin() || $user->isSupervisor()) {
            return $query;
        }

        if ($user->isManager()) {
            return $query->whereHas('claim.policy', function (Builder $policyQuery) use ($user) {
                $policyQuery->where('agent_id', $user->id);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->can('viewAny', ClaimNote::class);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListClaimNotes::route('/'),
            'create' => CreateClaimNote::route('/create'),
            'edit'   => EditClaimNote::route('/{record}/edit'),
        ];
    }
}