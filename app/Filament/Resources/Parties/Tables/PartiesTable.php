<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Resources\Parties\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Core\Filament\Utils\HasTable;

final class PartiesTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: static function (Collection $default_columns): void {
                $default_columns->unshift(...[
                    TextColumn::make('company.name')
                        ->searchable()
                        ->sortable()
                        ->toggleable(),
                    TextColumn::make('counterparty.name')
                        ->label(__('erp.fields.name'))
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('counterparty.vat_number')
                        ->searchable()
                        ->toggleable(),
                    IconColumn::make('is_customer')
                        ->boolean(),
                    IconColumn::make('is_supplier')
                        ->boolean(),
                ]);
            },
            filters: static function (Collection $default_filters): void {
                $default_filters->push(
                    Filter::make('intercompany')
                        ->label(__('erp.fields.intercompany'))
                        ->toggle()
                        ->query(static fn ($query) => $query->intercompany()),
                );
            },
        );
    }
}
