<?php

namespace App\Filament\Resources\InsuranceOffers;

use App\Filament\Resources\InsuranceOffers\Pages\CreateInsuranceOffer;
use App\Filament\Resources\InsuranceOffers\Pages\EditInsuranceOffer;
use App\Filament\Resources\InsuranceOffers\Pages\ListInsuranceOffers;
use App\Filament\Resources\InsuranceOffers\Schemas\InsuranceOfferForm;
use App\Filament\Resources\InsuranceOffers\Tables\InsuranceOffersTable;
use App\Models\InsuranceOffer;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class InsuranceOfferResource extends Resource
{
    protected static ?string $model = InsuranceOffer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Страхові довідники';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Страхові пропозиції';

    protected static ?string $modelLabel = 'Страхова пропозиція';

    protected static ?string $pluralModelLabel = 'Страхові пропозиції';

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
        return InsuranceOfferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InsuranceOffersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListInsuranceOffers::route('/'),
            'create' => CreateInsuranceOffer::route('/create'),
            'edit'   => EditInsuranceOffer::route('/{record}/edit'),
        ];
    }
}