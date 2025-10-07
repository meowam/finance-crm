<?php

namespace App\Filament\Resources\ClaimNotes;

use App\Filament\Resources\ClaimNotes\Pages\CreateClaimNote;
use App\Filament\Resources\ClaimNotes\Pages\EditClaimNote;
use App\Filament\Resources\ClaimNotes\Pages\ListClaimNotes;
use App\Filament\Resources\ClaimNotes\Schemas\ClaimNoteForm;
use App\Filament\Resources\ClaimNotes\Tables\ClaimNotesTable;
use App\Models\ClaimNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClaimNoteResource extends Resource
{
    protected static ?string $model = ClaimNote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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

    public static function getPages(): array
    {
        return [
            'index' => ListClaimNotes::route('/'),
            'create' => CreateClaimNote::route('/create'),
            'edit' => EditClaimNote::route('/{record}/edit'),
        ];
    }
}
