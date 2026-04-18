<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && ($user->isAdmin() || $user->isSupervisor());
    }

    public static function getLabel(): string
    {
        return 'Користувач';
    }

    public static function getPluralLabel(): string
    {
        return 'Користувачі';
    }

    public static function getNavigationLabel(): string
    {
        return 'Користувачі';
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        abort_unless($authUser instanceof User, 403);
        abort_if($authUser->isManager(), 403);

        $query = parent::getEloquentQuery();

        if ($authUser->isSupervisor()) {
            $query->where('role', 'manager');
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}'),
        ];
    }
}