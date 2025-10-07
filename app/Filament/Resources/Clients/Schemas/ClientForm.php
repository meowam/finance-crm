<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('type')
                    ->required()
                    ->default('individual'),
                TextInput::make('status')
                    ->required()
                    ->default('lead'),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('middle_name')
                    ->default(null),
                TextInput::make('company_name')
                    ->default(null),
                TextInput::make('primary_email')
                    ->email()
                    ->default(null),
                TextInput::make('primary_phone')
                    ->tel()
                    ->default(null),
                TextInput::make('document_number')
                    ->required(),
                TextInput::make('tax_id')
                    ->default(null),
                DatePicker::make('date_of_birth')
                    ->required(),
                Select::make('preferred_contact_method')
                    ->options(['phone' => 'Phone', 'email' => 'Email'])
                    ->default(null),
                TextInput::make('city')
                    ->default(null),
                TextInput::make('address_line')
                    ->default(null),
                TextInput::make('source')
                    ->default(null),
                TextInput::make('assigned_user_id')
                    ->numeric()
                    ->default(null),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
