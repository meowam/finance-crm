<?php

namespace App\Filament\Resources\InsuranceCompanies;

use App\Filament\Resources\InsuranceCompanies\Pages\CreateInsuranceCompany;
use App\Filament\Resources\InsuranceCompanies\Pages\EditInsuranceCompany;
use App\Filament\Resources\InsuranceCompanies\Pages\ListInsuranceCompanies;
use App\Filament\Resources\InsuranceCompanies\Schemas\InsuranceCompanyForm;
use App\Filament\Resources\InsuranceCompanies\Tables\InsuranceCompaniesTable;
use App\Models\InsuranceCompany;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class InsuranceCompanyResource extends Resource
{
    protected static ?string $model = InsuranceCompany::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Страхові довідники';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Страхові компанії';

    protected static ?string $modelLabel = 'Компанія страхування';

    protected static ?string $pluralModelLabel = 'Компанії страхування';

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
        return InsuranceCompanyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InsuranceCompaniesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListInsuranceCompanies::route('/'),
            'create' => CreateInsuranceCompany::route('/create'),
            'edit'   => EditInsuranceCompany::route('/{record}/edit'),
        ];
    }
}