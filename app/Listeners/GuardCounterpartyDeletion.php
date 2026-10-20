<?php

declare(strict_types=1);

namespace Modules\ERP\Listeners;

use Illuminate\Validation\ValidationException;
use Modules\CRM\Models\Counterparty;
use Modules\ERP\Models\Party;
use Modules\ERP\Scopes\BelongsToCompanyScope;

/**
 * A counterparty with a live party carries fiscal documents and cannot be deleted; a soft-deleted party
 * does not block a soft delete (the foreign key still blocks a hard delete). The CRM cannot see
 * parties, so the ERP enforces the rule when the counterparty is deleted.
 */
final class GuardCounterpartyDeletion
{
    public function handle(Counterparty $counterparty): void
    {
        $has_party = Party::query()
            ->withoutGlobalScope(BelongsToCompanyScope::class)
            ->where('counterparty_id', $counterparty->getKey())
            ->exists();

        if ($has_party) {
            throw ValidationException::withMessages([
                'counterparty' => ['A counterparty with a party cannot be deleted.'],
            ]);
        }
    }
}
