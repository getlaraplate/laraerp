<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Support;

use Illuminate\Support\Arr;

/**
 * The attributes of a document line taken from a repeater item, limited to the columns the page names.
 *
 * A repeater item is an untyped array, so its keys mean nothing to the analyser; the literal list of
 * keys does. The result is typed by that list, and Larastan checks each name against the columns the
 * line model declares, which `Arr::only()` (typed `array`) cannot give.
 */
final class LineAttributes
{
    /**
     * The entries of `$line` under `$keys`; a key the item lacks is left out, as `Arr::only()` does.
     *
     * @template TKey of string
     *
     * @param  array<array-key, mixed>  $line
     * @param  list<TKey>  $keys
     * @return array<TKey, mixed>
     */
    public static function only(array $line, array $keys): array
    {
        return Arr::only($line, $keys);
    }
}
