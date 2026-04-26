<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Clients\Schemas\ClientForm;
use App\Filament\Resources\Clients\Tables\ClientsTable;
use App\Models\Client;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    public static function getLabel(): string
    {
        return 'Клієнт';
    }

    public static function getPluralLabel(): string
    {
        return 'Клієнти';
    }

    public static function getNavigationLabel(): string
    {
        return 'Клієнти';
    }

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', Client::class);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', Client::class);
    }

    public static function form(Schema $schema): Schema
    {
        return ClientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientsTable::configure($table);
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
            'index'  => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'edit'   => EditClient::route('/{record}/edit'),
        ];
    }
}