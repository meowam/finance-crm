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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class InsuranceOfferResource extends Resource
{
    protected static ?string $model = InsuranceOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Страхові пропозиції';
    protected static ?string $modelLabel = 'Страхова пропозиція';
    protected static ?string $pluralModelLabel = 'Страхові пропозиції';

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User;
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
        return [
            //
        ];
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