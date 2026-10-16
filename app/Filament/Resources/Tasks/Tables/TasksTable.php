<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Resources\Tasks\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Core\Filament\Utils\HasTable;

final class TasksTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable($table, static function (Collection $columns): void {
            $columns->unshift(...[
                TextColumn::make('taxonomy.name')->searchable(),
                TextColumn::make('project.name')->placeholder('—'),
                TextColumn::make('site.name')->placeholder('—'),
            ]);
        });
    }
}
