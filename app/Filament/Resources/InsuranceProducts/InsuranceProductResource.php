<?php

namespace App\Filament\Resources\InsuranceProducts;

use App\Filament\Resources\InsuranceProducts\Pages\CreateInsuranceProduct;
use App\Filament\Resources\InsuranceProducts\Pages\EditInsuranceProduct;
use App\Filament\Resources\InsuranceProducts\Pages\ListInsuranceProducts;
use App\Filament\Resources\InsuranceProducts\Schemas\InsuranceProductForm;
use App\Filament\Resources\InsuranceProducts\Tables\InsuranceProductsTable;
use App\Models\InsuranceProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InsuranceProductResource extends Resource
{
    protected static ?string $model = InsuranceProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InsuranceProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InsuranceProductsTable::configure($table);
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
            'index' => ListInsuranceProducts::route('/'),
            'create' => CreateInsuranceProduct::route('/create'),
            'edit' => EditInsuranceProduct::route('/{record}/edit'),
        ];
    }
}
