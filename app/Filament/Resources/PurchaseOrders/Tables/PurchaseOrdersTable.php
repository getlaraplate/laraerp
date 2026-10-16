<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Resources\PurchaseOrders\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Core\Filament\Utils\HasTable;

final class PurchaseOrdersTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: static function (Collection $default_columns): void {
                $default_columns->unshift(...[
                    TextColumn::make('company.name')
                        ->toggleable(),
                    TextColumn::make('party.name')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('reference')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('status')
                        ->badge()
                        ->sortable(),
                    TextColumn::make('lines_count')
                        ->numeric()
                        ->sortable(),
                    TextColumn::make('lines_sum_qty_ordered')
                        ->numeric()
                        ->sortable()
                        ->default(0),
                    TextColumn::make('lines_sum_qty_received')
                        ->numeric()
                        ->sortable()
                        ->default(0),
                    TextColumn::make('ordered_at')
                        ->dateTime()
                        ->sortable(),
                ]);
            },
        );
    }
}
