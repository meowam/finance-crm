<?php

namespace App\Filament\Resources\Claims;

use App\Filament\Resources\Claims\Pages\CreateClaim;
use App\Filament\Resources\Claims\Pages\EditClaim;
use App\Filament\Resources\Claims\Pages\ListClaims;
use App\Filament\Resources\Claims\Schemas\ClaimForm;
use App\Filament\Resources\Claims\Tables\ClaimsTable;
use App\Models\Claim;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ClaimResource extends Resource
{
    protected static ?string $model = Claim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Страхові випадки';
    protected static ?string $modelLabel = 'Заява';
    protected static ?string $pluralModelLabel = 'Заяви';

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('viewAny', Claim::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('create', Claim::class) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return ClaimForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClaimsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();

        return parent::getEloquentQuery()
            ->with(['policy', 'reportedBy'])
            ->visibleTo($user);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListClaims::route('/'),
            'create' => CreateClaim::route('/create'),
            'edit'   => EditClaim::route('/{record}/edit'),
        ];
    }
}