<?php

namespace App\Filament\Resources\Policies;

use App\Filament\Resources\Policies\Pages\CreatePolicy;
use App\Filament\Resources\Policies\Pages\EditPolicy;
use App\Filament\Resources\Policies\Pages\ListPolicies;
use App\Filament\Resources\Policies\Schemas\PolicyForm;
use App\Filament\Resources\Policies\Tables\PoliciesTable;
use App\Models\Policy;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PolicyResource extends Resource
{
    protected static ?string $model = Policy::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel  = 'Поліси';
    protected static ?string $modelLabel       = 'Поліс';
    protected static ?string $pluralModelLabel = 'Поліси';

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->can('viewAny', Policy::class);
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->can('create', Policy::class);
    }

    public static function form(Schema $schema): Schema
    {
        return PolicyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PoliciesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();

        return parent::getEloquentQuery()->visibleTo($user);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPolicies::route('/'),
            'create' => CreatePolicy::route('/create'),
            'edit'   => EditPolicy::route('/{record}/edit'),
        ];
    }
}