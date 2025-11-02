<?php
namespace App\Filament\Resources\PolicyPayments;

use App\Filament\Resources\PolicyPayments\Pages\CreatePolicyPayment;
use App\Filament\Resources\PolicyPayments\Pages\EditPolicyPayment;
use App\Filament\Resources\PolicyPayments\Pages\ListPolicyPayments;
use App\Filament\Resources\PolicyPayments\Schemas\PolicyPaymentForm;
use App\Filament\Resources\PolicyPayments\Tables\PolicyPaymentsTable;
use App\Models\PolicyPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PolicyPaymentResource extends Resource
{
    protected static ?string $model = PolicyPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel                   = 'Оплати полісів';
    protected static ?string $modelLabel                        = 'Оплати полісів страхувань';
    protected static ?string $pluralModelLabel                  = 'Оплати полісів';

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

    public static function getPages(): array
    {
        return [
            'index'  => ListPolicyPayments::route('/'),
            'create' => CreatePolicyPayment::route('/create'),
            'edit'   => EditPolicyPayment::route('/{record}/edit'),
        ];
    }
}
