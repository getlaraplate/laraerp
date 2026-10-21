<?php

declare(strict_types=1);

namespace Modules\ERP\Observers;

use Modules\CRM\Models\Counterparty;
use Modules\ERP\Models\Party;
use Modules\ERP\Scopes\BelongsToCompanyScope;

/**
 * Keeps the customer and supplier aggregate columns of the {@see Counterparty} in line with its
 * parties of every company. The CRM cannot read ERP tables, so the ERP writes them.
 */
final class PartyObserver
{
    /**
     * A restored party never lives on a trashed counterparty: the subject comes back with it.
     */
    public function restoring(Party $party): void
    {
        $counterparty = Counterparty::query()->withTrashed()->find($party->counterparty_id);

        if ($counterparty instanceof Counterparty && $counterparty->trashed()) {
            $counterparty->restore();
        }
    }

    public function created(Party $party): void
    {
        $this->syncAggregates($party->counterparty_id);
    }

    /**
     * The aggregates only depend on the flags and on which counterparty the party belongs to, so any other
     * change of the party leaves them alone; a party that moves to another counterparty also refreshes the
     * one it leaves.
     */
    public function updated(Party $party): void
    {
        if (! $party->wasChanged(['is_customer', 'is_supplier', 'counterparty_id'])) {
            return;
        }

        $this->syncAggregates($party->counterparty_id);

        if ($party->wasChanged('counterparty_id')) {
            $this->syncAggregates($party->getOriginal('counterparty_id'));
        }
    }

    public function deleted(Party $party): void
    {
        $this->syncAggregates($party->counterparty_id);
    }

    public function restored(Party $party): void
    {
        $this->syncAggregates($party->counterparty_id);
    }

    private function syncAggregates(mixed $counterparty_id): void
    {
        if ($counterparty_id === null) {
            return;
        }

        $parties = Party::query()
            ->withoutGlobalScope(BelongsToCompanyScope::class)
            ->where('counterparty_id', $counterparty_id);

        Counterparty::query()->whereKey($counterparty_id)->update([
            'is_customer' => (clone $parties)->where('is_customer', true)->exists(),
            'is_supplier' => (clone $parties)->where('is_supplier', true)->exists(),
        ]);
    }
}
