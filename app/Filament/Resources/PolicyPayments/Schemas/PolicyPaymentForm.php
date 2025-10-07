<?php

namespace App\Filament\Resources\PolicyPayments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PolicyPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('policy_id')
                    ->required()
                    ->numeric(),
                DatePicker::make('due_date')
                    ->required(),
                DateTimePicker::make('paid_at'),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('заплановано'),
                TextInput::make('method')
                    ->default(null),
                TextInput::make('transaction_reference')
                    ->default(null),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
