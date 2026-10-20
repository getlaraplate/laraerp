<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Support;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Modules\ERP\Models\Party;

/**
 * A party picker that does not depend on the party table having a `name` column: the legal identity
 * lives on the CRM counterparty, so options, search and labels go through {@see Party}.
 */
final class PartySelect
{
    /**
     * @param  (Closure(Builder<Party>, Get): Builder<Party>)|null  $scope  Narrows the parties offered (for example customers only).
     */
    public static function make(string $name = 'party_id', ?Closure $scope = null): Select
    {
        return Select::make($name)
            ->searchable()
            ->preload()
            ->options(static fn (Get $get): array => Party::optionLabels(self::query($scope, $get)))
            ->getSearchResultsUsing(static fn (string $search, Get $get): array => Party::optionLabels(self::query($scope, $get), $search))
            ->getOptionLabelUsing(static fn (mixed $value): ?string => Party::query()->whereKey($value)->first()?->name);
    }

    /**
     * @param  (Closure(Builder<Party>, Get): Builder<Party>)|null  $scope
     * @return Builder<Party>
     */
    private static function query(?Closure $scope, Get $get): Builder
    {
        $query = Party::query();

        return $scope instanceof Closure ? $scope($query, $get) : $query;
    }
}
