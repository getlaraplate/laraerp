<?php

declare(strict_types=1);

namespace Modules\ERP\Observers;

use Modules\CRM\Services\OpportunityLifecycleService;
use Modules\ERP\Casts\QuoteStatus;
use Modules\ERP\Models\Party;
use Modules\ERP\Models\Quotation;
use Modules\ERP\Scopes\BelongsToCompanyScope;

/**
 * An accepted quotation makes its party a customer and closes the linked CRM opportunity as won.
 */
final readonly class QuotationObserver
{
    public function __construct(
        private OpportunityLifecycleService $opportunity_lifecycle_service,
    ) {}

    public function saved(Quotation $quotation): void
    {
        if (! $quotation->wasChanged('status') && $quotation->wasRecentlyCreated === false) {
            return;
        }

        if ($quotation->status !== QuoteStatus::Accepted) {
            return;
        }

        $this->promotePartyToCustomer($quotation);

        if ($quotation->opportunity_id !== null) {
            $this->opportunity_lifecycle_service->markWon($quotation->opportunity_id);
        }
    }

    private function promotePartyToCustomer(Quotation $quotation): void
    {
        $party = Party::query()
            ->withoutGlobalScope(BelongsToCompanyScope::class)
            ->find($quotation->party_id);

        if ($party instanceof Party && ! $party->is_customer) {
            $party->is_customer = true;
            $party->save();
        }
    }
}
