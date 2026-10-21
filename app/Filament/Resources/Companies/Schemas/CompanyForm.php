<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Modules\Core\Filament\Utils\HasForm;

final class CompanyForm
{
    use HasForm;

    public static function configure(Schema $schema): Schema
    {
        self::configureForm($schema);

        return $schema
            ->components([
                TextInput::make('slug')
                    ->required()
                    ->maxLength(64)
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('legal_name')
                    ->maxLength(255),
                TextInput::make('tax_id')
                    ->maxLength(32)
                    ->label(__('erp.fields.company_tax_id')),
                TextInput::make('fiscal_country')
                    ->required()
                    ->length(2)
                    ->default('IT')
                    ->placeholder('IT'),
                TextInput::make('fiscal_regime')
                    ->maxLength(4)
                    ->placeholder('RF01'),
                TextInput::make('legal_address_line')
                    ->maxLength(255),
                TextInput::make('legal_postal_code')
                    ->maxLength(16),
                TextInput::make('legal_city')
                    ->maxLength(128),
                TextInput::make('legal_province')
                    ->maxLength(8),
                TextInput::make('legal_country')
                    ->length(2)
                    ->default('IT'),
                TextInput::make('rea_office')
                    ->maxLength(8),
                TextInput::make('rea_number')
                    ->maxLength(32),
                TextInput::make('share_capital')
                    ->numeric()
                    ->minValue(0),
                Toggle::make('sole_shareholder'),
                TextInput::make('liquidation_status')
                    ->maxLength(2),
                TextInput::make('default_currency')
                    ->required()
                    ->length(3)
                    ->default('EUR')
                    ->placeholder('EUR'),
                Select::make('counterparty_id')
                    ->label(__('erp.fields.counterparty_id'))
                    ->relationship('counterparty', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Toggle::make('is_default'),
                KeyValue::make('settings')
                    ->keyLabel('Key')
                    ->valueLabel('Value')
                    ->addActionLabel('Add setting')
                    ->nullable(),
            ]);
    }
}
