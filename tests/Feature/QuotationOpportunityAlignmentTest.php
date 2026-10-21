<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\CRM\Casts\OpportunityStatus;
use Modules\CRM\Models\Counterparty;
use Modules\CRM\Models\Opportunity;
use Modules\CRM\Tests\Support\OpportunityStageTaxonomy;
use Modules\ERP\Casts\QuoteStatus;
use Modules\ERP\Casts\SalesOrderStatus;
use Modules\ERP\Models\Company;
use Modules\ERP\Models\Party;
use Modules\ERP\Models\Quotation;
use Modules\ERP\Models\SalesOrder;

uses(RefreshDatabase::class);

function alignmentOpportunity(Counterparty $counterparty, string $slug): Opportunity
{
    return Opportunity::query()->create([
        'counterparty_id' => $counterparty->getKey(),
        'stage_taxonomy_id' => OpportunityStageTaxonomy::insertMinimalId($slug),
        'name' => 'Deal ' . $slug,
        'status' => OpportunityStatus::Open,
        'expected_currency_doc' => 'EUR',
        'expected_currency_local' => 'EUR',
        'expected_fx_rate' => 1,
    ]);
}

function alignmentQuotation(Party $party, array $attributes = []): Quotation
{
    return Quotation::query()->create([
        'company_id' => $party->company_id,
        'party_id' => $party->getKey(),
        'currency' => 'EUR',
        'status' => QuoteStatus::Draft,
        'version' => 0,
        ...$attributes,
    ]);
}

it('keeps the quotation link on the opportunity table', function (): void {
    expect(Illuminate\Support\Facades\Schema::hasColumn((new Quotation)->getTable(), 'opportunity_id'))->toBeTrue();
});

it('rejects a quotation when the opportunity belongs to another counterparty', function (): void {
    $party_one = Party::factory()->create();
    $party_two = Party::factory()->create(['company_id' => $party_one->company_id]);
    $opportunity = alignmentOpportunity($party_two->counterparty, 'align-other-counterparty');

    try {
        alignmentQuotation($party_one, ['opportunity_id' => $opportunity->getKey()]);
        expect(false)->toBeTrue('expected ValidationException was not thrown');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('opportunity_id')
            ->and($exception->errors()['opportunity_id'][0] ?? '')->toContain('same counterparty');
    }
});

it('accepts a quotation for a party that is not a customer yet', function (): void {
    $party = Party::factory()->create(['is_customer' => false, 'is_supplier' => false]);

    $quotation = alignmentQuotation($party);

    expect($quotation->exists)->toBeTrue()
        ->and($party->fresh()->is_customer)->toBeFalsy();
});

it('makes the party a customer and marks the opportunity won when the quotation is accepted', function (): void {
    $party = Party::factory()->create(['is_customer' => false, 'is_supplier' => false]);
    $opportunity = alignmentOpportunity($party->counterparty, 'align-accepted');

    $quotation = alignmentQuotation($party, ['opportunity_id' => $opportunity->getKey()]);
    $quotation->update(['status' => QuoteStatus::Accepted]);

    expect($opportunity->fresh()->status)->toBe(OpportunityStatus::Won)
        ->and($opportunity->fresh()->won_at)->not->toBeNull()
        ->and($party->fresh()->is_customer)->toBeTruthy()
        ->and($party->counterparty->fresh()->is_customer)->toBeTrue();
});

it('allows quotations of different companies on one opportunity', function (): void {
    $counterparty = Counterparty::factory()->create();
    $company_a = Company::factory()->create();
    $company_b = Company::factory()->create();
    $party_a = Party::factory()->create(['counterparty_id' => $counterparty->getKey(), 'company_id' => $company_a->getKey()]);
    $party_b = Party::factory()->create(['counterparty_id' => $counterparty->getKey(), 'company_id' => $company_b->getKey()]);
    $opportunity = alignmentOpportunity($counterparty, 'align-two-companies');

    $first = alignmentQuotation($party_a, ['opportunity_id' => $opportunity->getKey()]);
    $second = alignmentQuotation($party_b, ['opportunity_id' => $opportunity->getKey()]);

    expect($first->exists)->toBeTrue()
        ->and($second->exists)->toBeTrue()
        ->and($first->company_id)->not->toBe($second->company_id);
});

it('still requires a customer for a sales order', function (): void {
    $party = Party::factory()->create(['is_customer' => false, 'is_supplier' => true]);

    expect(fn (): SalesOrder => SalesOrder::query()->create([
        'company_id' => $party->company_id,
        'party_id' => $party->getKey(),
        'currency' => 'EUR',
        'status' => SalesOrderStatus::Draft,
    ]))->toThrow(ValidationException::class);
});
