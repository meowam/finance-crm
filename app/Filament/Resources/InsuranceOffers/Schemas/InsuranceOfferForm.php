<?php

namespace App\Filament\Resources\InsuranceOffers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class InsuranceOfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('insurance_product_id')
                    ->required()
                    ->numeric(),
                TextInput::make('insurance_company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('offer_name')
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('coverage_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('duration_months')
                    ->required()
                    ->numeric()
                    ->default(12),
                TextInput::make('franchise')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Textarea::make('benefits')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('conditions')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
