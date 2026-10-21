<?php

declare(strict_types=1);

namespace Modules\ERP\Listeners;

use Illuminate\Validation\ValidationException;
use Modules\CRM\Models\Counterparty;
use Modules\ERP\Models\Party;
use Modules\ERP\Scopes\BelongsToCompanyScope;

/**
 * An organisational counterparty (no VAT number, no tax id, it only groups legal entities) never has a party.
 * The party side refuses to attach to a parent counterparty; this is the other direction: a counterparty
 * that already has a party cannot be placed under an identifier-less parent that has one.
 */
final class GuardOrganisationalCounterparty
{
    public function handle(Counterparty $counterparty): void
    {
        if ($counterparty->parent_counterparty_id === null || ! $counterparty->isDirty('parent_counterparty_id')) {
            return;
        }

        $parent = Counterparty::query()->withTrashed()->find($counterparty->parent_counterparty_id);

        if (! $parent instanceof Counterparty || ! blank($parent->vat_number) || ! blank($parent->tax_id)) {
            return;
        }

        $parent_has_party = Party::query()
            ->withoutGlobalScope(BelongsToCompanyScope::class)
            ->where('counterparty_id', $parent->getKey())
            ->exists();

        if ($parent_has_party) {
            throw ValidationException::withMessages([
                'parent_counterparty_id' => ['An organisational counterparty with a party cannot be a group parent.'],
            ]);
        }
    }
}
