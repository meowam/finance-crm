<?php

namespace App\Filament\Resources\InsuranceProducts;

use App\Filament\Resources\InsuranceProducts\Pages\CreateInsuranceProduct;
use App\Filament\Resources\InsuranceProducts\Pages\EditInsuranceProduct;
use App\Filament\Resources\InsuranceProducts\Pages\ListInsuranceProducts;
use App\Filament\Resources\InsuranceProducts\Schemas\InsuranceProductForm;
use App\Filament\Resources\InsuranceProducts\Tables\InsuranceProductsTable;
use App\Models\InsuranceProduct;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class InsuranceProductResource extends Resource
{
    protected static ?string $model = InsuranceProduct::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Страхові довідники';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Підвиди категорій';

    protected static ?string $modelLabel = 'Страховий продукт';

    protected static ?string $pluralModelLabel = 'Страхові продукти';

    protected static function getAuthUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    protected static function canViewReferenceDirectory(): bool
    {
        $user = static::getAuthUser();

        return $user instanceof User
            && ($user->isAdmin() || $user->isSupervisor() || $user->isManager());
    }

    protected static function canManageReferenceDirectory(): bool
    {
        $user = static::getAuthUser();

        return $user instanceof User
            && ($user->isAdmin() || $user->isSupervisor());
    }

    public static function canViewAny(): bool
    {
        return static::canViewReferenceDirectory();
    }

    public static function canCreate(): bool
    {
        return static::canManageReferenceDirectory();
    }

    public static function canEdit($record): bool
    {
        return static::canManageReferenceDirectory();
    }

    public static function canDelete($record): bool
    {
        return static::canManageReferenceDirectory();
    }

    public static function canDeleteAny(): bool
    {
        return static::canManageReferenceDirectory();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListInsuranceProducts::route('/'),
            'create' => CreateInsuranceProduct::route('/create'),
            'edit'   => EditInsuranceProduct::route('/{record}/edit'),
        ];
    }
}