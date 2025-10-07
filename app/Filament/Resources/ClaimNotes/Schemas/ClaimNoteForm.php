<?php

namespace App\Filament\Resources\ClaimNotes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ClaimNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('claim_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('visibility')
                    ->required()
                    ->default('внутрішня'),
                Textarea::make('note')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
