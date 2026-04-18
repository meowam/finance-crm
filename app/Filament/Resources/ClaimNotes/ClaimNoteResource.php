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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ClaimNoteResource extends Resource
{
    protected static ?string $model = ClaimNote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Нотатки';
    protected static ?string $modelLabel = 'Нотатка';
    protected static ?string $pluralModelLabel = 'Нотатки';

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
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();

        $query = parent::getEloquentQuery()->with(['claim', 'user']);

        if ($user instanceof User && $user->isManager()) {
            $query->whereHas('claim', function (Builder $claimQuery) use ($user) {
                $claimQuery->where('reported_by_id', $user->id);
            });
        }

        return $query;
    }

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return ! $user->isManager();
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