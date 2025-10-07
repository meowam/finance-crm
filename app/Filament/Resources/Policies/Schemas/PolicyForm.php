<?php

namespace App\Filament\Resources\Policies\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('policy_number')
                    ->required(),
                TextInput::make('client_id')
                    ->required()
                    ->numeric(),
                TextInput::make('insurance_offer_id')
                    ->required()
                    ->numeric(),
                TextInput::make('agent_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('status')
                    ->required()
                    ->default('чернетка'),
                DatePicker::make('effective_date'),
                DatePicker::make('expiration_date'),
                TextInput::make('premium_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('coverage_amount')
                    ->numeric()
                    ->default(null),
                TextInput::make('payment_frequency')
                    ->required()
                    ->default('одноразово'),
                TextInput::make('commission_rate')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
