<?php

namespace App\Filament\Resources\Claims\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('claim_number')
                    ->required(),
                TextInput::make('policy_id')
                    ->required()
                    ->numeric(),
                TextInput::make('reported_by_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('status')
                    ->required()
                    ->default('на розгляді'),
                DateTimePicker::make('reported_at'),
                DatePicker::make('loss_occurred_at'),
                TextInput::make('loss_location')
                    ->default(null),
                TextInput::make('cause')
                    ->default(null),
                TextInput::make('amount_claimed')
                    ->numeric()
                    ->default(null),
                TextInput::make('amount_reserve')
                    ->numeric()
                    ->default(null),
                TextInput::make('amount_paid')
                    ->numeric()
                    ->default(null),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('metadata')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
