<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Resources\JournalEntries\Tables;

use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Core\Filament\Utils\HasTable;
use Modules\ERP\Filament\Resources\JournalEntries\JournalEntryResource;
use Modules\ERP\Models\JournalEntry;

final class JournalEntriesTable
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
                    TextColumn::make('id')
                        ->sortable(),
                    TextColumn::make('posted_at')
                        ->dateTime()
                        ->sortable()
                        ->placeholder(__('app.filters.draft')),
                    IconColumn::make('is_posted')
                        ->boolean()
                        ->state(static fn (JournalEntry $record): bool => $record->posted_at !== null),
                    TextColumn::make('description')
                        ->limit(40)
                        ->toggleable(),
                ]);
            },
            actions: static function (Collection $default_actions): void {
                $default_actions->prepend(
                    Action::make('fullPageView')
                        ->label(__('erp.actions.details'))
                        ->url(static fn (JournalEntry $record): string => JournalEntryResource::getUrl('view', ['record' => $record])),
                );
            },
        );
    }
}
