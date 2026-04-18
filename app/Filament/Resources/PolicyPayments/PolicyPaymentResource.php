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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PolicyPaymentResource extends Resource
{
    protected static ?string $model = PolicyPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Оплати полісів';
    protected static ?string $modelLabel = 'Оплата полісу';
    protected static ?string $pluralModelLabel = 'Оплати полісів';

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

        $query = parent::getEloquentQuery()->with(['policy.client', 'policy.agent']);

        if ($user instanceof User && $user->isManager()) {
            $query->whereHas('policy', function (Builder $policyQuery) use ($user) {
                $policyQuery->where('agent_id', $user->id);
            });
        }

        return $query;
    }

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User;
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