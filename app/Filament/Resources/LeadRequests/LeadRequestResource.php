<?php

namespace App\Filament\Resources\LeadRequests;

use App\Filament\Resources\LeadRequests\Pages\CreateLeadRequest;
use App\Filament\Resources\LeadRequests\Pages\EditLeadRequest;
use App\Filament\Resources\LeadRequests\Pages\ListLeadRequests;
use App\Filament\Resources\LeadRequests\Schemas\LeadRequestForm;
use App\Filament\Resources\LeadRequests\Tables\LeadRequestsTable;
use App\Models\LeadRequest;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LeadRequestResource extends Resource
{
    protected static ?string $model = LeadRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Вхідні заявки';
    protected static ?string $modelLabel = 'Вхідна заявка';
    protected static ?string $pluralModelLabel = 'Вхідні заявки';

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->can('viewAny', LeadRequest::class);
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->can('create', LeadRequest::class);
    }

    public static function form(Schema $schema): Schema
    {
        return LeadRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();

        return parent::getEloquentQuery()
            ->with(['assignedUser', 'convertedClient'])
            ->visibleTo($user);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeadRequests::route('/'),
            'create' => CreateLeadRequest::route('/create'),
            'edit' => EditLeadRequest::route('/{record}/edit'),
        ];
    }
}