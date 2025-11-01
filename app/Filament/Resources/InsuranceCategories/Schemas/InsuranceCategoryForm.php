<?php
namespace App\Filament\Resources\InsuranceCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InsuranceCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Код')
                    ->placeholder('Напр., AUTO')
                    ->required()
                    ->minLength(2)
                    ->maxLength(30)
                    ->rule('regex:/^[A-Za-z]+$/')               
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'required' => 'Вкажіть код.',
                        'min'      => 'Код має містити щонайменше 2 латинські літери.',
                        'max'      => 'Код має містити не більше 30 символів.',
                        'regex'    => 'Код повинен складатися лише з латинських літер (A–Z).',
                    ]),

                TextInput::make('name')
                    ->label('Назва')
                    ->placeholder('Напр., Автострахування')
                    ->required()
                    ->minLength(2)
                    ->maxLength(150)
                    ->validationMessages([
                        'required' => 'Вкажіть назву.',
                        'min'      => 'Назва має містити щонайменше 2 символи.',
                        'max'      => 'Назва має містити не більше 150 символів.',
                    ]),

                Textarea::make('description')
                    ->label('Детальний опис')
                    ->placeholder('Опишіть категорію…')
                    ->required()
                    ->minLength(5)
                    ->maxLength(1000)
                    ->columnSpanFull()
                    ->validationMessages([
                        'required' => 'Вкажіть детальний опис.',
                        'min'      => 'Опис має містити щонайменше 5 символів.',
                        'max'      => 'Опис має містити не більше 1000 символів.',
                    ]),
            ]);
    }
}
