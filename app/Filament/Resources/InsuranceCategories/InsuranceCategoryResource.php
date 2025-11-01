<?php
namespace App\Filament\Resources\InsuranceCategories;

use App\Filament\Resources\InsuranceCategories\Pages\CreateInsuranceCategory;
use App\Filament\Resources\InsuranceCategories\Pages\EditInsuranceCategory;
use App\Filament\Resources\InsuranceCategories\Pages\ListInsuranceCategories;
use App\Filament\Resources\InsuranceCategories\Schemas\InsuranceCategoryForm;
use App\Filament\Resources\InsuranceCategories\Tables\InsuranceCategoriesTable;
use App\Models\InsuranceCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InsuranceCategoryResource extends Resource
{
    protected static ?string $model = InsuranceCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel  = 'Категорії страхувань';
    protected static ?string $modelLabel       = 'Страхування';
    protected static ?string $pluralModelLabel = 'Категорії страхувань';

    public static function form(Schema $schema): Schema
    {
        return InsuranceCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InsuranceCategoriesTable::configure($table);
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
            'index'  => ListInsuranceCategories::route('/'),
            'create' => CreateInsuranceCategory::route('/create'),
            'edit'   => EditInsuranceCategory::route('/{record}/edit'),
        ];
    }
}
