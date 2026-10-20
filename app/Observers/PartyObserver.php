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
    public function saved(Party $party): void
    {
        $this->syncAggregates($party);
    }

    public function deleted(Party $party): void
    {
        $this->syncAggregates($party);
    }

    public function restored(Party $party): void
    {
        $this->syncAggregates($party);
    }

    private function syncAggregates(Party $party): void
    {
        $counterparty_id = $party->counterparty_id;

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
