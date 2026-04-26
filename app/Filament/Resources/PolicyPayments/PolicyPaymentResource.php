<?php

namespace App\Filament\Resources\PolicyPayments;

use App\Filament\Resources\PolicyPayments\Pages\CreatePolicyPayment;
use App\Filament\Resources\PolicyPayments\Pages\EditPolicyPayment;
use App\Filament\Resources\PolicyPayments\Pages\ListPolicyPayments;
use App\Filament\Resources\PolicyPayments\Schemas\PolicyPaymentForm;
use App\Filament\Resources\PolicyPayments\Tables\PolicyPaymentsTable;
use App\Models\PolicyPayment;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PolicyPaymentResource extends Resource
{
    protected static ?string $model = PolicyPayment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Оплати полісів';
    protected static ?string $modelLabel = 'Оплата полісу';
    protected static ?string $pluralModelLabel = 'Оплати полісів';

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', PolicyPayment::class);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', PolicyPayment::class);
    }

    public static function canEdit($record): bool
    {
        return Gate::allows('update', $record);
    }

    public static function canDelete($record): bool
    {
        return Gate::allows('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        return Gate::allows('deleteAny', PolicyPayment::class);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return PolicyPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PolicyPaymentsTable::configure($table);
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
            ->with(['policy.client', 'policy.agent'])
            ->visibleTo($user);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPolicyPayments::route('/'),
            'create' => CreatePolicyPayment::route('/create'),
            'edit'   => EditPolicyPayment::route('/{record}/edit'),
        ];
    }
}
