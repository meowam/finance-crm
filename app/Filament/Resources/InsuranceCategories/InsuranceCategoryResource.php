<?php

namespace App\Filament\Resources\InsuranceCategories;

use App\Filament\Resources\InsuranceCategories\Pages\CreateInsuranceCategory;
use App\Filament\Resources\InsuranceCategories\Pages\EditInsuranceCategory;
use App\Filament\Resources\InsuranceCategories\Pages\ListInsuranceCategories;
use App\Filament\Resources\InsuranceCategories\Schemas\InsuranceCategoryForm;
use App\Filament\Resources\InsuranceCategories\Tables\InsuranceCategoriesTable;
use App\Models\InsuranceCategory;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class InsuranceCategoryResource extends Resource
{
    protected static ?string $model = InsuranceCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = 'Страхові довідники';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Категорії страхувань';

    protected static ?string $modelLabel = 'Страхування';

    protected static ?string $pluralModelLabel = 'Категорії страхувань';

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
        return InsuranceCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InsuranceCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
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